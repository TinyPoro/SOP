<?php
/**
 * Created by PhpStorm.
 * User: TinyPoro
 * Date: 2/18/20
 * Time: 6:33 PM
 */

namespace App\Helpers;


use App\Crawler\PuPHPeteerCrawler;

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

    public function getItemTypeAndItemSizeFromTitle($title) {
        $result = [
            'type' => '',
            'size' => ''
        ];

        if(preg_match('/Digital Art/ui', $title)){
            $result['type'] = "Digital art";
        }

        if(preg_match('/Canvas/ui', $title)){
            $result['type'] = "canvas";

            if(preg_match("/\d+ x \d+/", $title,$matches)){
                $result['size'] = $matches[0];
            }
        }

        if(preg_match('/Poster/ui', $title)){
            $result['type'] = "poster";

            if(preg_match("/\d+ x \d+/", $title,$matches)){
                $result['size'] = $matches[0];
            }
        }

        return $result;
    }
}