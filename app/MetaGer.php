<?php
namespace App;

use App;
use App\lib\TextLanguageDetect\TextLanguageDetect;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Jenssegers\Agent\Agent;
use LaravelLocalization;
use Log;
use Redis;

class MetaGer
{
    # Settings for the search
    protected $fokus;
    protected $eingabe;
    protected $q;
    protected $category;
    protected $time;
    protected $page;
    protected $lang;
    protected $cache = "";
    protected $site;
    protected $hostBlacklist   = [];
    protected $domainBlacklist = [];
    protected $stopWords       = [];
    protected $phrases         = [];
    protected $engines         = [];
    protected $results         = [];
    protected $ads             = [];
    protected $warnings        = [];
    protected $errors          = [];
    protected $addedHosts      = [];
    # Data about the query
    protected $ip;
    protected $language;
    protected $agent;
    # Configuration settings:
    protected $sumaFile;
    protected $mobile;
    protected $resultCount;
    protected $sprueche;
    protected $domainsBlacklisted = [];
    protected $urlsBlacklisted    = [];
    protected $url;
    protected $languageDetect;

    public function __construct()
    {
        $this->starttime = microtime(true);
        if (file_exists(config_path() . "/blacklistDomains.txt") && file_exists(config_path() . "/blacklistUrl.txt")) {
            # Read in blacklists:
            $tmp                      = file_get_contents(config_path() . "/blacklistDomains.txt");
            $this->domainsBlacklisted = explode("\n", $tmp);
            $tmp                      = file_get_contents(config_path() . "/blacklistUrl.txt");
            $this->urlsBlacklisted    = explode("\n", $tmp);
        } else {
            Log::warning("Achtung: Eine, oder mehrere Blacklist Dateien, konnten nicht geöffnet werden");
        }

        $this->languageDetect = new TextLanguageDetect();
        $this->languageDetect->setNameMode("2");
    }

    public function getHashCode()
    {
        $string = url()->full();
        return md5($string);
    }

    public function rankAll()
    {
        foreach ($this->engines as $engine) {
            $engine->rank($this);
        }
    }

    public function createView()
    {
        $viewResults = [];

        # We extract all necessary variables and pass them to our view:
        foreach ($this->results as $result) {
            $viewResults[] = get_object_vars($result);
        }

        # Of course we still need to write the log for the performed search:
        $this->createLogs();

        if ($this->fokus === "bilder") {
            switch ($this->out) {
                case 'results':
                    return view('metager3bilderresults')
                        ->with('results', $viewResults)
                        ->with('eingabe', $this->eingabe)
                        ->with('mobile', $this->mobile)
                        ->with('warnings', $this->warnings)
                        ->with('errors', $this->errors)
                        ->with('metager', $this)
                        ->with('browser', (new Agent())->browser());
                default:
                    return view('metager3bilder')
                        ->with('results', $viewResults)
                        ->with('eingabe', $this->eingabe)
                        ->with('mobile', $this->mobile)
                        ->with('warnings', $this->warnings)
                        ->with('errors', $this->errors)
                        ->with('metager', $this)
                        ->with('browser', (new Agent())->browser());
            }
        }

        switch ($this->out) {
            case 'results':
                return view('metager3results')
                    ->with('results', $viewResults)
                    ->with('eingabe', $this->eingabe)
                    ->with('mobile', $this->mobile)
                    ->with('warnings', $this->warnings)
                    ->with('errors', $this->errors)
                    ->with('metager', $this)
                    ->with('browser', (new Agent())->browser());
                break;
            case 'results-with-style':
                return view('metager3')
                    ->with('results', $viewResults)
                    ->with('eingabe', $this->eingabe)
                    ->with('mobile', $this->mobile)
                    ->with('warnings', $this->warnings)
                    ->with('errors', $this->errors)
                    ->with('metager', $this)
                    ->with('suspendheader', "yes")
                    ->with('browser', (new Agent())->browser());
                break;
            default:
                return view('metager3')
                    ->with('eingabe', $this->eingabe)
                    ->with('mobile', $this->mobile)
                    ->with('warnings', $this->warnings)
                    ->with('errors', $this->errors)
                    ->with('metager', $this)
                    ->with('browser', (new Agent())->browser());
                break;
        }
    }

    private function createLogs()
    {
        $redis = Redis::connection('redisLogs');
        try
        {
            $logEntry = "";
            $logEntry .= "[" . date(DATE_RFC822, mktime(date("H"), date("i"), date("s"), date("m"), date("d"), date("Y"))) . "]";
            $logEntry .= " pid=" . getmypid();
            $logEntry .= " ref=" . $this->request->header('Referer');
            $useragent = $this->request->header('User-Agent');
            $useragent = str_replace("(", " ", $useragent);
            $useragent = str_replace(")", " ", $useragent);
            $useragent = str_replace(" ", "", $useragent);
            $logEntry .= " time=" . round((microtime(true) - $this->starttime), 2) . " serv=" . $this->fokus;
            $logEntry .= " search=" . $this->eingabe;
            $redis->rpush('logs.search', $logEntry);
        } catch (\Exception $e) {
            return;
        }
    }

    public function removeInvalids()
    {
        $results = [];
        foreach ($this->results as $result) {
            if ($result->isValid($this)) {
                $results[] = $result;
            }

        }
        #$this->results = $results;
    }

    public function combineResults()
    {
        foreach ($this->engines as $engine) {
            foreach ($engine->results as $result) {
                if ($result->valid) {
                    $this->results[] = $result;
                }

            }
            foreach ($engine->ads as $ad) {
                $this->ads[] = $ad;
            }
        }
        uasort($this->results, function ($a, $b) {
            if ($a->getRank() == $b->getRank()) {
                return 0;
            }

            return ($a->getRank() < $b->getRank()) ? 1 : -1;
        });
        # Validate Results
        $newResults = [];
        foreach ($this->results as $result) {
            if ($result->isValid($this)) {
                $newResults[] = $result;
            }

        }
        $this->results = $newResults;

        $counter   = 0;
        $firstRank = 0;
        foreach ($this->results as $result) {
            if ($counter === 0) {
                $firstRank = $result->rank;
            }

            $counter++;
            $result->number = $counter;
            $confidence     = 0;
            if ($firstRank > 0) {
                $confidence = $result->rank / $firstRank;
            } else {
                $confidence = 0;
            }

            if ($confidence > 0.65) {
                $result->color = "#FF4000";
            } elseif ($confidence > 0.4) {
                $result->color = "#FF0080";
            } elseif ($confidence > 0.2) {
                $result->color = "#C000C0";
            } else {
                $result->color = "#000000";
            }

        }

        //Get current page form url e.g. &page=6
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $offset      = $currentPage - 1;

        //Create a new Laravel collection from the array data
        $collection = new Collection($this->results);

        //Define how many items we want to be visible in each page
        $perPage = $this->resultCount;

        //Slice the collection to get the items to display in current page
        $currentPageSearchResults = $collection->slice($offset * $perPage, $perPage)->all();

        # Now follows our boost implementation for these 20 links.
        $currentPageSearchResults = $this->parseBoost($currentPageSearchResults);

        # Now follows our Adgoal implementation for these 20 links.
        $currentPageSearchResults = $this->parseAdgoal($currentPageSearchResults);

        //Create our paginator and pass it to the view
        $paginatedSearchResults = new LengthAwarePaginator($currentPageSearchResults, count($collection), $perPage);
        $paginatedSearchResults->setPath('/meta/meta.ger3');
        foreach ($this->request->all() as $key => $value) {
            if ($key === "out") {
                continue;
            }

            $paginatedSearchResults->addQuery($key, $value);
        }

        $this->results = $paginatedSearchResults;

        if (LaravelLocalization::getCurrentLocale() === "en") {
            $this->ads = [];
        }

        $this->validated = false;
        if (isset($this->password)) {
            # We offer a paid API access where the advertising is hidden accordingly:
            # Currently it is only Uni-Mainz. Therefore we only check this one.
            $password = getenv('mainz');
            $eingabe  = $this->eingabe;
            $password = md5($eingabe . $password);
            if ($this->password === $password) {
                $this->ads       = [];
                $this->validated = true;
            }
        }

        if (count($this->results) <= 0) {
            $this->errors[] = trans('messages.no_results');
        }
    }

    public function parseBoost($results)
    {
        foreach ($results as $result) {
            if (preg_match('/^(http[s]?\:\/\/)?(www.)?amazon\.de/', $result->anzeigeLink)) {
                if (preg_match('/\?/', $result->anzeigeLink)) {
                    $result->link .= '&tag=boostmg01-21';
                } else {
                    $result->link .= '?tag=boostmg01-21';
                }
                $result->partnershop = true;

            }
        }
        return $results;
    }
    public function parseAdgoal($results)
    {
        $publicKey  = getenv('adgoal_public');
        $privateKey = getenv('adgoal_private');
        if ($publicKey === false) {
            return $results;
        }
        $tldList = "";
        try {
            foreach ($results as $result) {
                $link = $result->anzeigeLink;
                if (strpos($link, "http") !== 0) {
                    $link = "http://" . $link;
                }
                $tldList .= parse_url($link, PHP_URL_HOST) . ",";
                $result->tld = parse_url($link, PHP_URL_HOST);
            }
            $tldList = rtrim($tldList, ",");

            # Hash value
            $hash = md5("meta" . $publicKey . $tldList . "GER");

            # Query
            $query = urlencode($this->q);

            $link   = "https://api.smartredirect.de/api_v2/CheckForAffiliateUniversalsearchMetager.php?p=" . $publicKey . "&k=" . $hash . "&tld=" . $tldList . "&q=" . $query;
            $answer = json_decode(file_get_contents($link));

            # Now we only need to change the links for the advertisers:
            foreach ($answer as $el) {
                $hoster = $el[0];
                $hash   = $el[1];

                foreach ($results as $result) {
                    if ($hoster === $result->tld) {
                        # Here is an advertiser:
                        # Add the logo:
                        if ($result->image !== "") {
                            $result->logo = "https://img.smartredirect.de/logos_v2/60x30/" . $hash . ".gif";
                        } else {
                            $result->image = "https://img.smartredirect.de/logos_v2/120x60/" . $hash . ".gif";
                        }

                        # Add the link:
                        $publicKey = $publicKey;
                        $targetUrl = $result->anzeigeLink;
                        if (strpos($targetUrl, "http") !== 0) {
                            $targetUrl = "http://" . $targetUrl;
                        }

                        $gateHash            = md5($targetUrl . $privateKey);
                        $newLink             = "https://api.smartredirect.de/api_v2/ClickGate.php?p=" . $publicKey . "&k=" . $gateHash . "&url=" . urlencode($targetUrl) . "&q=" . $query;
                        $result->link        = $newLink;
                        $result->partnershop = true;
                    }
                }
            }
        } catch (\ErrorException $e) {
            return $results;
        }

        return $results;
    }

    public function createSearchEngines(Request $request)
    {

        if (!$request->has("eingabe")) {
            return;
        }

        # Check which search engines are enabled
        $xml                  = simplexml_load_file($this->sumaFile);
        $enabledSearchengines = [];
        $overtureEnabled      = false;
        $countSumas           = 0;
        $sumas                = $xml->xpath("suma");
        if ($this->fokus === "angepasst") {
            foreach ($sumas as $suma) {
                if ($request->has($suma["name"])
                    || ($this->fokus !== "bilder"
                        && ($suma["name"]->__toString() === "qualigo"
                            || $suma["name"]->__toString() === "similar_product_ads"
                            || (!$overtureEnabled && $suma["name"]->__toString() === "overtureAds")
                        )
                    )
                ) {

                    if (!(isset($suma['disabled']) && $suma['disabled']->__toString() === "1")) {
                        if ($suma["name"]->__toString() === "overture" || $suma["name"]->__toString() === "overtureAds") {
                            $overtureEnabled = true;
                        }
                        if ($suma["name"]->__toString() !== "qualigo" && $suma["name"]->__toString() !== "similar_product_ads" && $suma["name"]->__toString() !== "overtureAds") {
                            $countSumas += 1;
                        }

                        $enabledSearchengines[] = $suma;
                    }
                }
            }
        } else {
            foreach ($sumas as $suma) {
                $types = explode(",", $suma["type"]);
                if (in_array($this->fokus, $types)
                    || ($this->fokus !== "bilder"
                        && ($suma["name"]->__toString() === "qualigo"
                            || $suma["name"]->__toString() === "similar_product_ads"
                            || (!$overtureEnabled && $suma["name"]->__toString() === "overtureAds")
                        )
                    )
                ) {
                    if (!(isset($suma['disabled']) && $suma['disabled']->__toString() === "1")) {
                        if ($suma["name"]->__toString() === "overture" || $suma["name"]->__toString() === "overtureAds") {
                            $overtureEnabled = true;
                        }
                        if ($suma["name"]->__toString() !== "qualigo" && $suma["name"]->__toString() !== "similar_product_ads" && $suma["name"]->__toString() !== "overtureAds") {
                            $countSumas += 1;
                        }

                        $enabledSearchengines[] = $suma;
                    }
                }
            }
        }

        # Special rule for all search engines that belong to the mini searchers. These can all be queried together via a single link
        $subcollections = [];
        $tmp            = [];
        foreach ($enabledSearchengines as $engine) {
            if (isset($engine['minismCollection'])) {
                $subcollections[] = $engine['minismCollection']->__toString();
            } else {
                $tmp[] = $engine;
            }

        }
        $enabledSearchengines = $tmp;
        if (sizeof($subcollections) > 0) {
            $count                        = sizeof($subcollections) * 10;
            $minisucherEngine             = $xml->xpath('suma[@name="minism"]')[0];
            $subcollections               = urlencode("(" . implode(" OR ", $subcollections) . ")");
            $minisucherEngine["formData"] = str_replace("<<SUBCOLLECTIONS>>", $subcollections, $minisucherEngine["formData"]);
            $minisucherEngine["formData"] = str_replace("<<COUNT>>", $count, $minisucherEngine["formData"]);
            $enabledSearchengines[]       = $minisucherEngine;
        }

        #die(var_dump($enabledSearchengines));

        if ($countSumas <= 0) {
            $this->errors[] = trans('messages.no_engine_selected');
        }
        $engines = [];

        $siteSearchFailed = false;
        if (strlen($this->site) > 0) {
            # If a site search is to be performed, we check whether any of the search engines supports a site search at all:
            $enginesWithSite = 0;
            foreach ($enabledSearchengines as $engine) {
                if (isset($engine['hasSiteSearch']) && $engine['hasSiteSearch']->__toString() === "1") {
                    $enginesWithSite++;
                }
            }
            if ($enginesWithSite === 0) {
                $this->errors[]   = trans('messages.site_search_unsupported', ['site' => $this->site, 'link' => $this->generateSearchLink("web", false)]);
                $siteSearchFailed = true;
            } else {
                $this->warnings[] = trans('messages.site_search_active', ['site' => $this->site]);
            }

        }

        $typeslist = [];
        $counter   = 0;

        foreach ($enabledSearchengines as $engine) {

            if (!$siteSearchFailed && strlen($this->site) > 0 && (!isset($engine['hasSiteSearch']) || $engine['hasSiteSearch']->__toString() === "0")) {

                continue;
            }
            # If this search engine should not be enabled at all
            $path = "App\Models\parserSkripte\\" . ucfirst($engine["package"]->__toString());

            if (!file_exists(app_path() . "/Models/parserSkripte/" . ucfirst($engine["package"]->__toString()) . ".php")) {
                Log::error("Konnte " . $engine["name"] . " nicht abfragen, da kein Parser existiert");
                continue;
            }

            $time = microtime();

            try
            {
                $tmp = new $path($engine, $this);
            } catch (\ErrorException $e) {
                Log::error("Konnte " . $engine["name"] . " nicht abfragen." . var_dump($e));
                continue;
            }

            if ($tmp->enabled && isset($this->debug)) {
                $this->warnings[] = $tmp->service . "   Connection_Time: " . $tmp->connection_time . "    Write_Time: " . $tmp->write_time . " Insgesamt:" . ((microtime() - $time) / 1000);
            }

            if ($tmp->isEnabled()) {
                $engines[]                 = $tmp;
                $this->sockets[$tmp->name] = $tmp->fp;
            }

        }

        # Now we also go through all categories of the settings and save the names of the search engines they contain.
        $foki = [];
        foreach ($sumas as $suma) {
            if ((!isset($suma['disabled']) || $suma['disabled'] === "") && (!isset($suma['userSelectable']) || $suma['userSelectable']->__toString() === "1")) {
                if (isset($suma['type'])) {
                    $f = explode(",", $suma['type']->__toString());
                    foreach ($f as $tmp) {
                        $name                                    = $suma['name']->__toString();
                        $foki[$tmp][$suma['name']->__toString()] = $name;
                    }
                } else {
                    $name                                        = $suma['name']->__toString();
                    $foki["andere"][$suma['name']->__toString()] = $name;
                }
            }
        }

        # The names of the currently active search engines are also saved.
        $realEngNames = [];
        foreach ($enabledSearchengines as $realEng) {
            $nam = $realEng["name"]->__toString();
            if ($nam !== "qualigo" && $nam !== "overtureAds") {
                $realEngNames[] = $nam;
            }
        }
        # Then these two lists are compared (one of the focus lists for each focus) to find out whether they might be identical. If this is the case, the user has apparently configured the search engines of a complete focus. The focus is adjusted accordingly.
        foreach ($foki as $fok => $engs) {
            $isFokus      = true;
            $fokiEngNames = [];
            foreach ($engs as $eng) {
                $fokiEngNames[] = $eng;
            }
            foreach ($fokiEngNames as $fen) {
                if (!in_array($fen, $realEngNames)) {
                    $isFokus = false;
                }
            }
            foreach ($realEngNames as $ren) {
                if (!in_array($ren, $fokiEngNames)) {
                    $isFokus = false;
                }
            }
            if ($isFokus) {
                $this->fokus = $fok;
            }
        }

        # Now an elementary step follows.
        # We wait for the response of the search engines, because we cannot continue before that.
        # but of course not forever.
        # The connection is established at this point and our request has already been sent.
        # We now give the search engine up to 500ms to respond.

        # We count the search engines that were answered from the cache:
        $enginesToLoad = 0;
        $canBreak      = false;
        foreach ($engines as $engine) {
            if ($engine->cached) {
                $enginesToLoad--;
                if ($overtureEnabled && ($engine->name === "overture" || $engine->name === "overtureAds")) {
                    $canBreak = true;
                }

            }
        }
        $enginesToLoad += count($engines);
        $loadedEngines = 0;
        $timeStart     = microtime(true);
        while (true) {
            $time          = (microtime(true) - $timeStart) * 1000;
            $loadedEngines = intval(Redis::hlen('search.' . $this->getHashCode()));
            if ($overtureEnabled && (Redis::hexists('search.' . $this->getHashCode(), 'overture') || Redis::hexists('search.' . $this->getHashCode(), 'overtureAds'))) {
                $canBreak = true;
            }

            # Termination condition
            if ($time < 500) {
                if (($enginesToLoad === 0 || $loadedEngines >= $enginesToLoad) && $canBreak) {
                    break;
                }

            } elseif ($time >= 500 && $time < $this->time) {
                if (($enginesToLoad === 0 || ($loadedEngines / ($enginesToLoad * 1.0)) >= 0.8) && $canBreak) {
                    break;
                }

            } else {
                break;
            }
            usleep(50000);
        }

        #exit;
        foreach ($engines as $engine) {
            if (!$engine->loaded) {
                try {
                    $engine->retrieveResults();
                } catch (\ErrorException $e) {
                    Log::error($e);

                }
            }
        }

        # and discard the rest:
        foreach ($engines as $engine) {
            if (!$engine->loaded) {
                $engine->shutdown();
            }

        }

        $this->engines = $engines;
    }

    public function parseFormData(Request $request)
    {
        if ($request->input('encoding', '') !== "utf8") {
            # In earlier versions, when the encoding parameter did not exist yet, the data was transmitted in ISO-8859-1
            $input = $request->all();
            foreach ($input as $key => $value) {
                $input[$key] = mb_convert_encoding("$value", "UTF-8", "ISO-8859-1");
            }
            $request->replace($input);
        }
        $this->url = $request->url();
        # First we check the entered settings:
        # FOCUS
        $this->fokus = trans('fokiNames.'
            . $request->input('focus', 'web'));
        if (strpos($this->fokus, ".")) {
            $this->fokus = trans('fokiNames.web');
        }

        # SUMA-FILE
        if (App::isLocale("en")) {
            $this->sumaFile = config_path() . "/sumas.xml";
        } else {
            $this->sumaFile = config_path() . "/sumas.xml";
        }
        if (!file_exists($this->sumaFile)) {
            die("Suma-File konnte nicht gefunden werden");
        }

        # Search input:
        $this->eingabe = trim($request->input('eingabe', ''));
        if (strlen($this->eingabe) === 0) {
            $this->warnings[] = trans('messages.no_search_term');
        }
        $this->q = $this->eingabe;

        # IP:
        $this->ip = $request->ip();

        # Language:
        if (isset($_SERVER['HTTP_LANGUAGE'])) {
            $this->language = $_SERVER['HTTP_LANGUAGE'];
        } else {
            $this->language = "";
        }
        # Category
        $this->category = $request->input('category', '');
        # Request Times:
        $this->time = $request->input('time', 5000);

        # Page
        $this->page = $request->input('page', 1);
        # Lang
        $this->lang = $request->input('lang', 'all');
        if ($this->lang !== "de" && $this->lang !== "en" && $this->lang !== "all") {
            $this->lang = "all";
        }
        $this->agent  = new Agent();
        $this->mobile = $this->agent->isMobile();

        #Quotes
        $this->sprueche = $request->input('sprueche', 'off');
        if ($this->sprueche === "off") {
            $this->sprueche = true;
        } else {
            $this->sprueche = false;
        }

        # Results per page:
        $this->resultCount = $request->input('resultCount', '20');

        # Sometimes we have to adjust parameters to comply with the search settings:
        if ($request->has('dart')) {
            $this->time       = 10000;
            $this->warnings[] = trans('messages.dart_europe');
        }
        if ($this->time <= 500 || $this->time > 20000) {
            $this->time = 5000;
        }
        if ($request->has('minism') && ($request->has('fportal') || $request->has('harvest'))) {
            $input    = $request->all();
            $newInput = [];
            foreach ($input as $key => $value) {
                if ($key !== "fportal" && $key !== "harvest") {
                    $newInput[$key] = $value;
                }
            }
            $request->replace($newInput);
        }
        if (App::isLocale("en")) {
            $this->sprueche = "off";
        }
        if ($this->resultCount <= 0 || $this->resultCount > 200) {
            $this->resultCount = 1000;
        }
        if ($request->has('onenewspageAll') || $request->has('onenewspageGermanyAll')) {
            $this->time  = 5000;
            $this->cache = "cache";
        }
        if ($request->has('tab')) {
            if ($request->input('tab') === "off") {
                $this->tab = "_blank";
            } else {
                $this->tab = "_self";
            }
        } else {
            $this->tab = "_blank";
        }
        if ($request->has('password')) {
            $this->password = $request->input('password');
        }

        if ($request->has('quicktips')) {
            $this->quicktips = false;
        } else {
            $this->quicktips = true;
        }

        $this->out = $request->input('out', "html");
        if ($this->out !== "html" && $this->out !== "json" && $this->out !== "results" && $this->out !== "results-with-style") {
            $this->out = "html";
        }

        $this->request = $request;
    }

    public function checkSpecialSearches(Request $request)
    {
        # Site Search:
        if (preg_match("/(.*)\bsite:(\S+)(.*)/si", $this->q, $match)) {
            $this->site = $match[2];
            $this->q    = $match[1] . $match[3];
        }
        if ($request->has('site')) {
            $this->site = $request->input('site');
        }
        # If the search query is extended with the keyword "-host:*", certain hosts should not be displayed
        # We check whether this is the case here:
        while (preg_match("/(.*)(^|\s)-host:(\S+)(.*)/si", $this->q, $match)) {
            $this->hostBlacklist[] = $match[3];
            $this->q               = $match[1] . $match[4];
        }
        if (sizeof($this->hostBlacklist) > 0) {
            $hostString = "";
            foreach ($this->hostBlacklist as $host) {
                $hostString .= $host . ", ";
            }
            $hostString       = rtrim($hostString, ", ");
            $this->warnings[] = "Ergebnisse von folgenden Hosts werden nicht angezeigt: \"" . $hostString . "\"";
        }
        # If the search query is extended with the keyword "-domain:*", certain domains should not be displayed
        # We check whether this is the case here:
        while (preg_match("/(.*)(^|\s)-domain:(\S+)(.*)/si", $this->q, $match)) {
            $this->domainBlacklist[] = $match[3];
            $this->q                 = $match[1] . $match[4];
        }
        if (sizeof($this->domainBlacklist) > 0) {
            $domainString = "";
            foreach ($this->domainBlacklist as $domain) {
                $domainString .= $domain . ", ";
            }
            $domainString     = rtrim($domainString, ", ");
            $this->warnings[] = "Ergebnisse von folgenden Domains werden nicht angezeigt: \"" . $domainString . "\"";
        }

        # All words prefixed with "-" should be excluded from the search.
        # We check whether this is the case here:
        while (preg_match("/(.*)(^|\s)-(\S+)(.*)/si", $this->q, $match)) {
            $this->stopWords[] = $match[3];
            $this->q           = $match[1] . $match[4];
        }
        if (sizeof($this->stopWords) > 0) {
            $stopwordsString = "";
            foreach ($this->stopWords as $stopword) {
                $stopwordsString .= $stopword . ", ";
            }
            $stopwordsString  = rtrim($stopwordsString, ", ");
            $this->warnings[] = trans('messages.exclusion_search', ['words' => $stopwordsString]);
        }

        # Notification about a phrase search
        $p   = "";
        $tmp = $this->q;
        while (preg_match("/(.*)\"(.+)\"(.*)/si", $tmp, $match)) {
            $tmp             = $match[1] . $match[3];
            $this->phrases[] = strtolower($match[2]);
        }
        foreach ($this->phrases as $phrase) {
            $p .= "\"$phrase\", ";
        }
        $p = rtrim($p, ", ");
        if (sizeof($this->phrases) > 0) {
            $this->warnings[] = trans('messages.phrase_search', ['phrases' => $p]);
        }

    }

    public function getFokus()
    {
        return $this->fokus;
    }

    public function getIp()
    {
        return $this->ip;
    }

    public function getEingabe()
    {
        return $this->eingabe;
    }

    public function getQ()
    {
        return $this->q;
    }

    public function getUrl()
    {
        return $this->url;
    }
    public function getTime()
    {
        return $this->time;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function getLang()
    {
        return $this->lang;
    }

    public function getSprueche()
    {
        return $this->sprueche;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function getPhrases()
    {
        return $this->phrases;
    }

    public function getSumaFile()
    {
        return $this->sumaFile;
    }

    public function getUserHostBlacklist()
    {
        return $this->hostBlacklist;
    }

    public function getUserDomainBlacklist()
    {
        return $this->domainBlacklist;
    }

    public function getDomainBlacklist()
    {
        return $this->domainsBlacklisted;
    }

    public function getUrlBlacklist()
    {
        return $this->urlsBlacklisted;
    }
    public function getLanguageDetect()
    {
        return $this->languageDetect;
    }
    public function getStopWords()
    {
        return $this->stopWords;
    }
    public function getHostCount($host)
    {
        if (isset($this->addedHosts[$host])) {
            return $this->addedHosts[$host];
        } else {
            return 0;
        }
    }
    public function addHostCount($host)
    {
        $hash = md5($host);
        if (isset($this->addedHosts[$hash])) {
            $this->addedHosts[$hash] += 1;
        } else {
            $this->addedHosts[$hash] = 1;
        }
    }
    public function getSite()
    {
        return $this->site;
    }
    public function addLink($link)
    {
        if (strpos($link, "http://") === 0) {
            $link = substr($link, 7);
        }

        if (strpos($link, "https://") === 0) {
            $link = substr($link, 8);
        }

        if (strpos($link, "www.") === 0) {
            $link = substr($link, 4);
        }

        $link = trim($link, "/");
        $hash = md5($link);
        if (isset($this->addedLinks[$hash])) {
            return false;
        } else {
            $this->addedLinks[$hash] = 1;

            return true;
        }
    }

    public function generateSearchLink($fokus, $results = true)
    {
        $requestData          = $this->request->except('page');
        $requestData['focus'] = $fokus;
        if ($results) {
            $requestData['out'] = "results";
        } else {
            $requestData['out'] = "";
        }

        $link = action('MetaGerSearch@search', $requestData);
        return $link;
    }

    public function generateQuicktipLink()
    {
        $link = action('MetaGerSearch@quicktips');

        return $link;
    }

    public function generateSiteSearchLink($host)
    {
        $host        = urlencode($host);
        $requestData = $this->request->except(['page', 'out']);
        $requestData['eingabe'] .= " site:$host";
        $requestData['focus'] = "web";
        $link                 = action('MetaGerSearch@search', $requestData);
        return $link;
    }

    public function generateRemovedHostLink($host)
    {
        $host        = urlencode($host);
        $requestData = $this->request->except(['page', 'out']);
        $requestData['eingabe'] .= " -host:$host";
        $link = action('MetaGerSearch@search', $requestData);
        return $link;
    }

    public function generateRemovedDomainLink($domain)
    {
        $domain      = urlencode($domain);
        $requestData = $this->request->except(['page', 'out']);
        $requestData['eingabe'] .= " -domain:$domain";
        $link = action('MetaGerSearch@search', $requestData);
        return $link;
    }

    public function getTab()
    {
        return $this->tab;
    }
    public function getResults()
    {
        return $this->results;
    }
    public function popAd()
    {
        if (count($this->ads) > 0) {
            return get_object_vars(array_shift($this->ads));
        } else {
            return null;
        }

    }
    public function getImageProxyLink($link)
    {
        $requestData        = [];
        $requestData["url"] = $link;
        $link               = action('Pictureproxy@get', $requestData);
        return $link;
    }
    public function showQuicktips()
    {
        return $this->quicktips;
    }
}
