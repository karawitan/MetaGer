<?php

namespace app\Models\parserSkripte;

use App\Models\Searchengine;
use Symfony\Component\DomCrawler\Crawler;

class Mojeek extends Searchengine
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
            $crawler->filter('ul.results-standard > li')->each(function (Crawler $node, $i) {
                $linkNode = $node->filter('a.ob');
                if ($linkNode->count() === 0) {
                    return;
                }
                $link        = $linkNode->attr('href');
                $anzeigeLink = $linkNode->text();

                $titleNode = $node->filter('h2 > a');
                $title     = $titleNode->count() > 0 ? $titleNode->text() : $anzeigeLink;

                $descrNode = $node->filter('p.s');
                $descr     = $descrNode->count() > 0 ? $descrNode->text() : '';

                $this->counter++;
                $this->results[] = new \App\Models\Result(
                    $this->engine,
                    $title,
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
