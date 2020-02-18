<?php
/**
 * Created by PhpStorm.
 * User: TinyPoro
 * Date: 12/23/19
 * Time: 11:40 PM
 */

namespace App\Crawler;


use Nesk\Puphpeteer\Puppeteer;
use Nesk\Rialto\Data\JsFunction;

class PuPHPeteerCrawler
{
    /**
     * Puppeteer browser
     *
     * @var string
     */
    private $browser;

    public function __construct()
    {
        $puppeteer = new Puppeteer([
            'executable_path' => env('NODE_PATH', '/usr/local/bin/node')
        ]);
        $this->browser = $puppeteer->launch([
            'args' => ['--no-sandbox', '--disable-setuid-sandbox']
        ]);
    }

    public function createNewPage()
    {
        return $this->browser->newPage();
    }

    public function getElementsAttribute($page, $selector, $attribute)
    {
        return $page->evaluate(JsFunction::createWithBody("
            let results = [];
            
            let elements = document.querySelectorAll('" . $selector . "')
            
            elements.forEach((element) => {
                results.push(element." . $attribute . ");
            });
            
            return results;
        "));
    }

    public function getElementAttribute($page, $selector, $attribute)
    {
        return $page->evaluate(JsFunction::createWithBody("
            let element = document.querySelector('" . $selector . "')
            
            return (null === element) ? '' : element." . $attribute
        ));
    }

    public function execJsFunction($page, $function)
    {
        return $page->evaluate(JsFunction::createWithBody($function));
    }
}