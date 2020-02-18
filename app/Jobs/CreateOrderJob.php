<?php

namespace App\Jobs;

use App\Helpers\ShopifyHelpers;
use App\Models\Item;
use App\Models\Order;
use App\Models\ShopifyImage;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Trello\Client;

class CreateOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $trelloClient;
    private $shopifyHelpers;

    private $orderNumber;
    private $date;
    private $customerName;
    private $customerEmail;
    private $linkToOrder;
    private $items;

    public function __construct( $orderNumber,
                                 $date,
                                 $customerName,
                                 $customerEmail,
                                 $linkToOrder,
                                 $items)
    {
        $this->orderNumber = $orderNumber;
        $this->date = $date;
        $this->customerName = $customerName;
        $this->customerEmail = $customerEmail;
        $this->linkToOrder = $linkToOrder;
        $this->items = $items;

        $trelloClient = new Client();
        $trelloClient->authenticate(env('TRELLO_KEY'), env('TRELLO_SECRET'), Client::AUTH_URL_CLIENT_ID);

        $this->trelloClient = $trelloClient;
        $this->shopifyHelpers = new ShopifyHelpers();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try{
            \DB::beginTransaction();

            //tạo đơn hàng
            $order = Order::create([
                'order_number' => $this->get('orderNumber'),
                'customer_name' => $this->get('customerName'),
                'customer_email' => $this->get('customerEmail'),
                'link_to_order' => $this->get('linkToOrder'),
                'order_date' => $this->get('date'),
            ]);

            // đồng bộ google drive (đến tầng khách hàng)
            $monthFolderName = $order->order_date->format("M Y");
            $monthFolder = $this->createGoogleDriveDir("/", $monthFolderName);

            $dayFolderName = $order->order_date->format("M d Y");
            $dayFolder = $this->createGoogleDriveDir($monthFolder['path']."/", $dayFolderName);

            $customerFolderName = $order->customer_name;
            $customerFolder = $this->createGoogleDriveDir($dayFolder['path']."/", $customerFolderName);

            $imageDisk = "shopify";

            $hasValidImage = false;

            //tạo item
            $items = $this->get('items');
            $finalNote = "";

            foreach ($items as $item) {
                $itemName = Arr::get($item, 'itemName', '');
                $numberOfItem = Arr::get($item, 'numberOfItem', '');
                $itemType = Arr::get($item, 'itemType', '');
                $itemSize = Arr::get($item, 'itemSize', '');
                $images = Arr::get($item, 'images', '');
                $notes = Arr::get($item, 'notes', '');

                $item = Item::create([
                    'item_name' => $itemName,
                    'number_of_item' => $numberOfItem,
                    'item_type' => $itemType,
                    'item_size' => $itemSize,
                    'notes' => implode(", ", $notes),
                    'order_id' => $order->id,
                ]);

                $finalNote .= $item->notes . ", ";


                $productFolderName = $this->shopifyHelpers->getGoogleDriveProductName($item->item_name, $item->item_type, $item->item_size);
                $productFolder = $this->createGoogleDriveDir($customerFolder['path']."/", $productFolderName);


                // xử lý images
                foreach ($images as $image) {
                    $path =  $order->id."/".$item->id."/".uniqid();

                    Storage::disk($imageDisk)->put($path, file_get_contents($image));

                    ShopifyImage::create([
                        'disk' => $imageDisk,
                        'path' => $path,
                        'order_id' => $order->id
                    ]);

                    $filePath = Storage::disk($imageDisk)->path($path);
                    $fileName = basename($filePath);

                    $this->uploadGoogleDriveFile($productFolder['path']."/", $fileName, $filePath);

                    if($this->shopifyHelpers->checkValidShopifyImage($filePath)) {
                        $hasValidImage = true;
                    }
                }
            }

            if(!$hasValidImage) {
                $to_name = $order->customer_name;
                $to_email = $order->customer_email;

                $data = [
                    "name" => $to_name,
                    "body" => "Ảnh mô tả sản phẩm cho đơn hàng $order->link_to_order của bạn không đạt chất lượng. Bạn vui lòng gửi lại ảnh bằng cách phản hồi email này!"
                ];
                Mail::send('emails.mail', $data, function($message) use ($to_name, $to_email) {
                    $message->to($to_email, $to_name)
                        ->subject("<Noble Pawtrait> xin cung cấp lại ảnh");
                    $message->from(env('MAIL_USERNAME'),'Noble Pawtrait');
                });
            }

            //đồng bộ trello
            $boardId = env("TRELLO_BOARD_ID");

            $customerFolderUrl = $this->getGoogleDriveUrl($customerFolder['path']);
            $cardName = $order->customer_name;
            $cardDesc = $hasValidImage ?
                "- Link google drive: $customerFolderUrl\n- Note của khách hàng: $finalNote" :
                "- Link google drive: $customerFolderUrl\n- Note của khách hàng: $finalNote\n- NIR";

            $listName = $dayFolderName;
            $list = $this->createTrelloBoardList($boardId, $listName);
            $card = $this->createTrelloListCard($list['id'], $cardName, $cardDesc);

            \DB::commit();
        }catch (\Exception $e){
            \DB::rollback();

            Log::error($e->getMessage());
        }
    }

    private function get($name){
        return $this->$name;
    }

    private function checkGoogleDriveDirExisted($path, $dirName, $recursive = false) {
        $contents = collect(Storage::cloud()->listContents($path, $recursive));

        $dir = $contents
            ->where('type', '=', 'dir')
            ->where('filename', '=', $dirName)
            ->first();

        return $dir;
    }

    private function checkGoogleDriveFileExisted($path, $fileName, $recursive = false) {
        $contents = collect(Storage::cloud()->listContents($path, $recursive));

        $file = $contents
            ->where('type', '=', 'file')
            ->where('filename', '=', $fileName)
            ->first();

        return $file;
    }

    private function createGoogleDriveDir($path, $dirName, $duplicate = false) {
        if($dir = $this->checkGoogleDriveDirExisted($path, $dirName)){
            if(!$duplicate) {
                return $dir;
            }
        }

        Storage::cloud()->makeDirectory($path.$dirName);

        return $this->checkGoogleDriveDirExisted($path, $dirName);
    }

    private function uploadGoogleDriveFile($path, $fileName, $filePath, $duplicate = false) {
        if($file = $this->checkGoogleDriveFileExisted($path, $fileName)){
            if(!$duplicate) {
                return $file;
            }
        }

        Storage::cloud()->put($path.$fileName, file_get_contents($filePath));
    }

    private function getGoogleDriveUrl($path) {
        return Storage::cloud()->url($path);
    }

    private function checkTrelloBoardListExist($boardId, $listName) {
        $lists = collect($this->trelloClient->api('boards')->lists()->all($boardId));

        $list = $lists
            ->where('name', '=', $listName)
            ->first();

        return $list;
    }

    private function createTrelloBoardList($boardId, $listName, $duplicate = false) {
        if($list = $this->checkTrelloBoardListExist($boardId, $listName)) {
            if(!$duplicate) {
                return $list;
            }
        }

        $list = $this->trelloClient->api('boards')->lists()->create($boardId, [
            'name' => $listName
        ]);

        return $list;
    }

    private function checkTrelloListCardExist($listId, $cardName) {
        $cards = collect($this->trelloClient->api('lists')->cards()->all($listId));

        $card = $cards
            ->where('name', '=', $cardName)
            ->first();

        return $card;
    }

    private function createTrelloListCard($listId, $cardName, $cardDesc, $duplicate = false) {
        if($card = $this->checkTrelloListCardExist($listId, $cardName)) {
            if(!$duplicate) {
                $oldDesc = $card->desc;
                $newDesc = "$oldDesc\n****\n$cardDesc";

                $card = $this->trelloClient->api('cards')->update($card->id, $cardName, [
                    'desc' => $newDesc
                ]);

                return $card;
            }
        }

        $card = $this->trelloClient->api('lists')->cards()->create($listId, $cardName, [
            'desc' => $cardDesc
        ]);

        return $card;
    }
}
