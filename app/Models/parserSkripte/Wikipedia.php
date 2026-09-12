<?php

namespace app\Models\parserSkripte;

use App\Models\Searchengine;
use Symfony\Component\DomCrawler\Crawler;

class Wikipedia extends Searchengine
{
    public $results = [];

    public function __construct(\SimpleXMLElement $engine, \App\MetaGer $metager)
    {
        parent::__construct($engine, $metager);
    }

    public function loadResults($result)
    {
        try
        {
            $crawler = new Crawler($result);
            $crawler->filter('ul.mw-search-results > li')->each(function (Crawler $node, $i) {
                $linkNode = $node->filter('div.mw-search-result-heading > a');
                if ($linkNode->count() === 0) {
                    return;
                }
                $link        = 'https://en.wikipedia.org' . $linkNode->attr('href');
                $anzeigeLink = $linkNode->text();

                $descrNode = $node->filter('div.searchresult');
                $descr     = $descrNode->count() > 0 ? $descrNode->text() : '';

                $this->counter++;
                $this->results[] = new \App\Models\Result(
                    $this->engine,
                    $anzeigeLink,
                    $link,
                    $anzeigeLink,
                    $descr,
                    $this->gefVon,
                    $this->counter
                );
            });
        } catch (\ErrorException $e) {
            return;
        }
    }
}
