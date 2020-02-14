<?php

namespace App\Console\Commands;

use Trello\Client;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestSopCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:sop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private $trelloClient;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $trelloClient = new Client();
        $trelloClient->authenticate(env('TRELLO_KEY'), env('TRELLO_SECRET'), Client::AUTH_URL_CLIENT_ID);

        $this->trelloClient = $trelloClient;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = "Phuong Tuan";
        $product = "My Lady Custom Pet Portrait";
        $type = "Digital Art";
        $size = "3*4";
        $images = [
            [
                "disk" => "shopify",
                "path" => "test.png"
            ]
        ];

        $today = Carbon::now();

        // đồng bộ google drive
        $dayFolderName = $today->format("M d Y");
        $dayFolder = $this->createGoogleDriveDir("/", $dayFolderName);

        $customerFolder = $this->createGoogleDriveDir($dayFolder['path']."/", $name);

        $productFolderName ="$product - $size";
        $productFolder = $this->createGoogleDriveDir($customerFolder['path']."/", $productFolderName);

        foreach ($images as $image) {
            $disk = $image['disk'];
            $path = $image['path'];

            $filePath = Storage::disk($disk)->path($path);
            $fileName = basename($filePath);

            $this->uploadGoogleDriveFile($productFolder['path']."/", $fileName, $filePath);
        }

        //đồng bộ trello
        $boardId = "gpvg8mgP";

        $customerFolderUrl = $this->getGoogleDriveUrl($customerFolder['path']);
        $cardName = $name;
        $cardDesc = "Link google drive: $customerFolderUrl";

        $listName = $dayFolderName;
        $list = $this->createTrelloBoardList($boardId, $listName);
        $card = $this->createTrelloListCard($list['id'], $cardName, $cardDesc);
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
