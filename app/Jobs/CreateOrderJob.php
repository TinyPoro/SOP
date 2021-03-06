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

    public $tries = 5;
    public $timeout = 3000;

    private $trelloClient;
    private $shopifyHelpers;

    private $orderData;

    public function __construct( $orderData)
    {
        $this->orderData = $orderData;

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
        try {
            $orderId = Arr::get($this->orderData, "id", "");
            $orderNumber = Arr::get($this->orderData,"order_number");
            $date = Arr::get($this->orderData,"created_at");
            $date = Carbon::createFromTimeString($date);

            $customer = Arr::get($this->orderData,"customer");
            $customerName = Arr::get($customer, 'first_name', '') . " " . Arr::get($customer, 'last_name', '');
            $customerEmail = Arr::get($customer, 'email', '');

            $totalPrice = Arr::get($this->orderData,'total_price', '');

            $lineItems = Arr::get($this->orderData,"line_items");

            $items = [];
            foreach ($lineItems as $lineItem) {
                $itemTitle = Arr::get($lineItem, 'title', '');
                $numberOfItem = Arr::get($lineItem, 'quantity', 0);
                $itemVariantTitle = Arr::get($lineItem, 'variant_title', '');

                $properties = Arr::get($lineItem, 'properties', []);

                $images = [];
                $notes = [];

                foreach ($properties as $property) {
                    $name = Arr::get($property, 'name', null);
                    $value = Arr::get($property, 'value', null);

                    if($name) {
                        if(preg_match("/^Uploaded\s+image\s*(\d+)?/", $name, $matches)) {
                            $number = Arr::get($matches, 1, 1);

                            $images[$number] = $value;
                        }

                        if(preg_match("/^Notes\s*(\d+)?/", $name, $matches)) {
                            $number = Arr::get($matches, 1, 1);

                            $notes[$number] = $value;
                        }
                    }
                }

                $items[] = [
                    'itemTitle' => $itemTitle,
                    'numberOfItem' => $numberOfItem,
                    'itemVariantTitle' => $itemVariantTitle,
                    'images' => $images,
                    'notes' => $notes,
                ];
            }

            $shippingMethods = [];
            $shippingLines = Arr::get($this->orderData,"shipping_lines");
            foreach ($shippingLines as $shippingLine) {
                $title = Arr::get($shippingLine, 'title', '');

                $shippingMethods[] = $title;
            }

            $shippingMethods = implode(", ", $shippingMethods);

            $shippingAddress = Arr::get($this->orderData,"shipping_address", []);
            $shippingName = Arr::get($shippingAddress, 'name', '');
            $shippingAddress1 = Arr::get($shippingAddress, 'address1', '');
            $shippingCity = Arr::get($shippingAddress, 'city', '');
            $shippingProvinceCode = Arr::get($shippingAddress, 'province_code', '');
            $shippingZip = Arr::get($shippingAddress, 'zip', '');
            $shippingCountry = Arr::get($shippingAddress, 'country', '');
            $shippingPhone = Arr::get($shippingAddress, 'phone', '');

            $shippingAddressText = "$shippingName\n$shippingAddress1\n$shippingCity $shippingProvinceCode $shippingZip\n$shippingCountry\n$shippingPhone";


            $this->storeOrder($orderId,
                $orderNumber,
                $date,
                $customerName,
                $customerEmail,
                $totalPrice,
                $shippingMethods,
                $items,
                $shippingAddressText);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            throw $e;
        }
    }

    public function storeOrder($orderId,
                               $orderNumber,
                               $date,
                               $customerName,
                               $customerEmail,
                               $totalPrice,
                               $shippingMethods,
                               $items,
                               $shippingAddressText)
    {
        try{
            \DB::beginTransaction();

            //tạo đơn hàng
            $order = Order::create([
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'order_date' => $date,
                'total_price' => $totalPrice,
                'shipping_method' => $shippingMethods,
                'shipping_address' => $shippingAddressText,
            ]);

            // phân quyền root
            $this->changeGoogleDriveFolderToEditToAnyone(env('GOOGLE_DRIVE_FOLDER_ID'), 'reader', 'anyone', false);

            // đồng bộ google drive (đến tầng khách hàng)
            $monthFolderName = $order->order_date->format("F Y");
            $monthFolder = $this->createGoogleDriveDir("/", $monthFolderName);

            $dayFolderName = $order->order_date->format("F d, Y");
            $dayFolder = $this->createGoogleDriveDir($monthFolder['path']."/", $dayFolderName);

            $customerFolderName = $orderNumber . " - " .$order->customer_name;
            $customerFolder = $this->createGoogleDriveDir($dayFolder['path']."/", $customerFolderName);

            $this->changeGoogleDriveFolderToEditToAnyone($customerFolder['basename'], 'writer', 'anyone', false);

            $imageDisk = "shopify";

            $hasValidImage = false;

            //tạo item

            $imagePosition = 0;
            $notePosition = 0;

            $itemTitleString = "";
            $itemVariantString = "";

            $trelloImageDes = "";

            foreach ($items as $itemKey => $item) {
                $itemTitle = Arr::get($item, 'itemTitle', '');
                $numberOfItem = Arr::get($item, 'numberOfItem', '');
                $itemVariantTitle = Arr::get($item, 'itemVariantTitle', '');
                $images = Arr::get($item, 'images', []);
                $notes = Arr::get($item, 'notes', []);

                $itemTitleString .= $itemTitle . ", ";
                $itemVariantString .= $itemVariantTitle . ", ";

                $itemNote = "";
                foreach ($notes as $noteKey => $note) {
                    $itemNote .= "+ " . $this->shopifyHelpers->getGoogleDriveNoteName($order->order_number, $notePosition + $noteKey) . ": " . $note . "\n";
                }

                $item = Item::create([
                    'item_title' => $itemTitle,
                    'number_of_item' => $numberOfItem,
                    'item_variant_title' => $itemVariantTitle,
                    'item_name' => "$itemTitle - $itemVariantTitle",
                    'notes' => $itemNote,
                    'order_id' => $order->id,
                ]);

                $productFolderName = ($itemKey + 1) . " - $item->item_name";
                $productFolder = $this->createGoogleDriveDir($customerFolder['path']."/", $productFolderName);

                // xử lý images
                foreach ($images as $imageKey => $image) {
                    $imageNumber = $imagePosition + $imageKey;

                    $trelloImageDes .= "[$imageNumber]($image) ";

                    $imagePath =  $order->id."/".$item->id."/".$this->shopifyHelpers->getGoogleDriveImageName($order->order_number, $imageNumber);

                    $imageSrc = $this->shopifyHelpers->getImageSrcFromCdnUrl($image);
                    Storage::disk($imageDisk)->put($imagePath, $this->getImageContent($imageSrc));

                    ShopifyImage::create([
                        'disk' => $imageDisk,
                        'path' => $imagePath,
                        'url' => $image,
                        'item_id' => $item->id
                    ]);

                    $filePath = Storage::disk($imageDisk)->path($imagePath);
                    $fileName = basename($filePath);

                    $uploadedFile = $this->uploadGoogleDriveFile($productFolder['path']."/", $fileName, $filePath);
                    $this->changeGoogleDriveFolderToEditToAnyone($uploadedFile['basename'], 'reader', 'anyone', false);

                    if($this->shopifyHelpers->checkValidShopifyImage($filePath)) {
                        $hasValidImage = true;
                    }
                }

                try {
                    $maxImageKey = max(array_keys($images));
                } catch (\Exception $e) {
                    $maxImageKey = 0;
                }

                try {
                    $maxNotesKey = max(array_keys($notes));
                } catch (\Exception $e) {
                    $maxNotesKey = 0;
                }

                $maxKey = max($maxImageKey, $maxNotesKey);

                $imagePosition += $maxKey;
                $notePosition += $maxKey;
            }

//            if(!$hasValidImage) {
//                //gửi email lấy lại ảnh
//                $to_name = $order->customer_name;
//                $to_email = $order->customer_email;
//
//                $data = [
//                    "name" => $to_name,
//                    "body" => "Ảnh mô tả sản phẩm cho đơn hàng của bạn không đạt chất lượng. Bạn vui lòng gửi lại ảnh bằng cách phản hồi email này!"
//                ];
//                Mail::send('emails.mail', $data, function($message) use ($to_name, $to_email) {
//                    $message->to($to_email, $to_name)
//                        ->subject("<Noble Pawtrait> xin cung cấp lại ảnh");
//                    $message->from(env('MAIL_USERNAME'),'Noble Pawtrait');
//                });
//            }

            //đồng bộ trello
            $boardId = env("TRELLO_BOARD_ID");

            $customerFolderUrl = $this->getGoogleDriveUrl($customerFolder['path']);
            $cardName = $orderNumber . " - " . $order->customer_name;
            $cardDesc = $hasValidImage ?
                "- Tên sản phẩm: $itemTitleString\n- Loại sản phẩm: $itemVariantString\n- Link google drive: $customerFolderUrl\n- Photos: $trelloImageDes \n- Note của khách hàng: \n" . $order->getNoteTextForTrello() :
                "- Tên sản phẩm: $itemTitleString\n- Loại sản phẩm: $itemVariantString\n- Link google drive: $customerFolderUrl\n- Photos: $trelloImageDes \n- Note của khách hàng: \n" . $order->getNoteTextForTrello() . "\n- NIR";

            $listName = $dayFolderName;
            $list = $this->createTrelloBoardList($boardId, $listName);
            $card = $this->createTrelloListCard($list['id'], $cardName, $cardDesc);

            $order->link_to_gd = $customerFolderUrl;
            $order->save();

            \DB::commit();
        }catch (\Exception $e){
            \DB::rollback();
            dump($e->getMessage());
            throw $e;

        }
    }

    private function getImageContent($link) {
        if(!$link) {
            throw new \Exception("no link!");
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $output = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if($httpcode == 200) {
            return $output;
        } else {
            throw new \Exception("HTTP_CODE_ERROR");
        }
    }

    private function checkGoogleDriveDirExisted($path, $dirName, $recursive = false) {
        $contents = collect(Storage::cloud()->listContents($path, $recursive));

        $dir = $contents
            ->where('type', '=', 'dir')
            ->where('name', '=', $dirName)
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
        $dirName = str_replace("/", "-", $dirName);

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

        return $this->checkGoogleDriveFileExisted($path, $fileName);
    }

    private function getGoogleDriveUrl($path) {
        return Storage::cloud()->url($path);
    }

    private function changeGoogleDriveFolderToEditToAnyone($baseName, $role, $type, $allowDiscovery = false){
        $service = Storage::cloud()->getAdapter()->getService();
        $permission = new \Google_Service_Drive_Permission();
        $permission->setRole($role);
        $permission->setType($type);
        $permission->setAllowFileDiscovery($allowDiscovery);

        $service->permissions->create($baseName, $permission);
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
                $card = $this->trelloClient->api('cards')->update($card['id'], [
                    'desc' => $cardDesc
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
