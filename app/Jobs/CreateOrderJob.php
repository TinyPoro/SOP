<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\ShopifyImage;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Trello\Client;

class CreateOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $trelloClient;

    private $name;
    private $product;
    private $type;
    private $size;
    private $images;
    private $orderDate;

    public function __construct($name, $product, $type, $size, $images, $orderDate)
    {
        $this->name = $name;
        $this->product = $product;
        $this->type = $type;
        $this->size = $size;
        $this->images = $images;
        $this->orderDate = $orderDate;

        $trelloClient = new Client();
        $trelloClient->authenticate(env('TRELLO_KEY'), env('TRELLO_SECRET'), Client::AUTH_URL_CLIENT_ID);

        $this->trelloClient = $trelloClient;
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

            $order = Order::create([
                'name' => $this->get('name'),
                'product' => $this->get('product'),
                'type' => $this->get('type'),
                'size' => $this->get('size'),
                'order_date' => Carbon::createFromFormat("d-m-Y", $this->get('orderDate')),
            ]);

            // đồng bộ google drive
            $dayFolderName = $order->order_date->format("M d Y");
            $dayFolder = $this->createGoogleDriveDir("/", $dayFolderName);

            $customerFolder = $this->createGoogleDriveDir($dayFolder['path']."/", $order->name);

            $productFolderName ="$order->product - $order->size";
            $productFolder = $this->createGoogleDriveDir($customerFolder['path']."/", $productFolderName);


            // xử lý images
            $disk = "shopify";

            foreach ($this->get('images') as $image) {
                $path =  $order->id."/".uniqid();

                Storage::disk($disk)->put($path, file_get_contents($image));

                ShopifyImage::create([
                    'disk' => $disk,
                    'path' => $path,
                    'order_id' => $order->id
                ]);

                $filePath = Storage::disk($disk)->path($path);
                $fileName = basename($filePath);

                $this->uploadGoogleDriveFile($productFolder['path']."/", $fileName, $filePath);
            }

            //đồng bộ trello
            $boardId = env("TRELLO_BOARD_ID");

            $customerFolderUrl = $this->getGoogleDriveUrl($customerFolder['path']);
            $cardName = $order->name;
            $cardDesc = "Link google drive: $customerFolderUrl";

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
                return $card;
            }
        }

        $card = $this->trelloClient->api('lists')->cards()->create($listId, $cardName, [
            'desc' => $cardDesc
        ]);

        return $card;
    }
}
