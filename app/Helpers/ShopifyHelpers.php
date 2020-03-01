<?php
/**
 * Created by PhpStorm.
 * User: TinyPoro
 * Date: 2/18/20
 * Time: 6:33 PM
 */

namespace App\Helpers;


use App\Crawler\PuPHPeteerCrawler;
use Illuminate\Support\Arr;

class ShopifyHelpers
{
    public function getImageSrcFromCdnUrl($url)
    {
        $puPHPeteerCrawler = new PuPHPeteerCrawler();
        $page = $puPHPeteerCrawler->createNewPage();
        $page->goto($url, ['waitUntil' => 'load']);

        $imageSrc = $puPHPeteerCrawler->getElementAttribute($page, "#previewImage", "src");

        return $imageSrc;
    }

    public function getItemTypeAndItemSizeFromTitle($title)
    {
        $result = [
            'type' => '',
            'size' => ''
        ];

        if(preg_match('/Digital Art/ui', $title)){
            $result['type'] = "Digital art";
        }

        if(preg_match('/Canvas/ui', $title)){
            $result['type'] = "Canvas";

            if(preg_match("/\d+ x \d+/", $title,$matches)){
                $result['size'] = $matches[0];
            }
        }

        if(preg_match('/Poster/ui', $title)){
            $result['type'] = "Poster";

            if(preg_match("/\d+ x \d+/", $title,$matches)){
                $result['size'] = $matches[0];
            }
        }

        return $result;
    }

    public function getGoogleDriveProductName($itemTitle, $itemVariantTitle)
    {
        return "$itemTitle - $itemVariantTitle";
    }

    public function getGoogleDriveImageName($orderId, $n)
    {
        return "$orderId-image-$n";
    }

    public function getGoogleDriveNoteName($orderId, $n)
    {
        return "$orderId-note-$n";
    }


    private function getGoogleDriveSize($itemSize) {
        switch ($itemSize) {
            case "8 x 10":
                return "2400 x 3000";
                break;
            case "12 x 18":
                return "3600 x 5400";
                break;
            case "16 x 24":
                return "4800 x 7200";
                break;
            default:
                return "";
        }
    }

    public function checkValidShopifyImage($filePath) {
        if(filesize($filePath) <= 500000) return false;

        if($this->getImagePixels($filePath) <= 900 * 900) return false;

        if($this->getDpiImageMagick($filePath) < 70) return false;

        return true;
    }

    private function getImagePixels($filePath)
    {
        try {
            $imageSize = getimagesize($filePath);

            return $imageSize[0] * $imageSize[1];
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getDpiImageMagick($filePath)
    {
        $cmd = 'identify -quiet -format "%x" '.$filePath;
        @exec(escapeshellcmd($cmd), $data);

        if($data && is_array($data)){
            $data = Arr::get($data, 0, null);
            if(!$data) return 72;

            $data = explode(' ', $data);

            $value = Arr::get($data, 0, 72);
            $type = Arr::get($data, 1, null);

            if(!$type){
                return $value;
            }elseif($type == 'PixelsPerCentimeter'){
                return ceil($value * 2.54);
            }elseif($type == 'PixelsPerInch'){
                return $value;
            }
        }

        return 72;
    }
}