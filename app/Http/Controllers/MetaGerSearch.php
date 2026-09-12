<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App;
use App\MetaGer;


class MetaGerSearch extends Controller
{
    public function search(Request $request, MetaGer $metager)
    {
        #die($request->header('User-Agent'));
        $time = microtime();
        # Parse and save the supplied form data:
        $metager->parseFormData($request);
        #if($metager->getFokus() !== "bilder" )
        #{
            # Check for special searches:
            $metager->checkSpecialSearches($request);
        #}
        # Create all search engines
        $metager->createSearchEngines($request);

        # Rank all results before merging:
        $metager->rankAll();

        # Combine the results of the search engines:
        $metager->combineResults();

        # Create the output:
        return $metager->createView();
    }

    public function quicktips(Request $request)
    {
        $q = $request->input('q', '');

        # First the quote
        $spruecheFile = storage_path() . "/app/public/sprueche.txt";
        if( file_exists($spruecheFile) && $request->has('sprueche') )
        {
            $sprueche = file($spruecheFile);
            $spruch = $sprueche[array_rand($sprueche)];
        }else
        {
            $spruch = "";
        }

        # The manual quick tips:
        $file = storage_path() . "/app/public/qtdata.csv";
        
        $mquicktips = [];
        if( file_exists($file) && $q !== '')
        {
            $file = fopen($file, 'r');
            while (($line = fgetcsv($file)) !== FALSE) {
                $words = array_slice($line,3);
                $isIn = FALSE;
                foreach($words as $word){
                        $word = strtolower($word);
                        if(strpos($q, $word) !== FALSE){
                                $isIn = TRUE;
                                break;
                        }
                }
                if($isIn === TRUE){
                        $quicktip = array('QT_Type' => "MQT");
                        $quicktip["URL"] = $line[0];
                        $quicktip["title"] = $line[1];
                        $quicktip["descr"] = $line[2];
                        $mquicktips[] = $quicktip;
                }
        }
        fclose($file);
        }

        # Wikipedia Quicktip
        $quicktips = [];
        $url = "http://de.wikipedia.org/w/api.php?action=query&titles=".urlencode(implode("_",array_diff(explode(" ",$q),array("wikipedia"))))."&prop=info|extracts|categories&inprop=url|displaytitle&exintro&exsentences=3&format=json";
        $decodedResponse = json_decode($this->get($url), true);
        if( isset($decodedResponse["query"]["pages"]) )
        {
            foreach($decodedResponse["query"]["pages"] as $result)
            {
                if( isset($result['displaytitle']) && isset($result['fullurl']) && isset($result['extract']) )
                {
                    $quicktip = [];
                    $quicktip["title"] = $result['displaytitle'];
                    $quicktip["URL"] = $result['fullurl'];
                    $quicktip["descr"] = strip_tags($result['extract']);
                    $quicktip['gefVon'] = trans('messages.wikipedia_source');

                    $quicktips[] = $quicktip;
                }
            }
        }
        $mquicktips = array_merge($mquicktips, $quicktips);

        # And of course the "did you know":
        $file = storage_path() . "/app/public/tips.txt";
        if( file_exists($file) )
        {
            $tips = file($file);
            $tip = $tips[array_rand($tips)];

            $mquicktips[] = ['title' => 'Wussten Sie schon?', 'descr' => $tip, 'URL' => '/tips'];   
        }   

        # And the ad links:
        $file = storage_path() . "/app/public/ads.txt";
        if( file_exists($file) )
        {
            $ads = json_decode(file_get_contents($file), true);
            $ad = $ads[array_rand($ads)];

            $mquicktips[] = ['title' => $ad['title'], 'descr' => $ad['descr'], 'URL' => $ad['URL']];   
        }   
        return view('quicktip')
            ->with('spruch', $spruch)
            ->with('mqs', $mquicktips);

            
    }

    public function tips()
    {
        $file = storage_path() . "/app/public/tips.txt";
        $tips = [];
        if( file_exists($file) )
        {
            $tips = file($file);
        }
        return view('tips')
            ->with('title', 'MetaGer - Tipps & Tricks')
            ->with('tips', $tips);
    }

    function get($url) {
        return file_get_contents($url);
    } 

}