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
    /**
     * PuPHPeteerCrawler
     *
     * @var PuPHPeteerCrawler
     */
    private $puPHPeteerCrawler;

    /**
     * Create a new command instance.
     *
     * @param PuPHPeteerCrawler $puPHPeteerCrawler
     * @return void
     */
    public function __construct(PuPHPeteerCrawler $puPHPeteerCrawler)
    {
        $this->puPHPeteerCrawler = $puPHPeteerCrawler;
    }

    public function getImageSrcFromCdnUrl($url)
    {
        $page = $this->puPHPeteerCrawler->createNewPage();
        $page->goto($url, ['waitUntil' => 'load']);

        $imageSrc = $this->puPHPeteerCrawler->getElementAttribute($page, "#previewImage", "src");

        return $imageSrc;
    }
}