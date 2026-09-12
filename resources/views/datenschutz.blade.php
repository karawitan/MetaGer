@extends('layouts.subPages')

@section('title', $title )

@section('navbarFocus.datenschutz', 'class="active"')

@section('content')
<h1>{{ trans('datenschutz.head') }}</h1>
<p>{{ trans('datenschutz.general.1') }} <a href="https://suma-ev.de/presse/Sicher-suchen-UND-finden-mit-MetaGer.html">{{ trans('datenschutz.general.2') }}</a></p>
<p>{!! trans('datenschutz.general.3') !!}</p>
<h2>{{ trans('datenschutz.policy.1') }}</h2>
<ul id="privacyList">
	<li>{{ trans('datenschutz.policy.2') }}
		<a href="http://www.heise.de/security/meldung/Fingerprinting-Viele-Browser-sind-ohne-Cookies-identifizierbar-1982976.html" target="_blank">{{ trans('datenschutz.policy.3') }}</a> {{ trans('datenschutz.policy.4') }}
	</li>
	<li >
		{{ trans('datenschutz.policy.5') }}
	</li>
	<li>{{ trans('datenschutz.policy.6') }}</li>
	<li>{{ trans('datenschutz.policy.7') }} <a href="http://forum.suma-ev.de/viewtopic.php?f=3&amp;t=43&amp;sid=c994b628153235dfef098ba6fea3d60e" target="_blank">{{ trans('datenschutz.policy.8') }}</a></li>
	<li>{{ trans('datenschutz.policy.9') }}</li>
	<li>{{ trans('datenschutz.policy.10') }} <a href="/spende/">{{ trans('datenschutz.policy.11') }}</a> {{ trans('datenschutz.policy.12') }} <a href="http://suma-ev.de/" target="_blank">SUMA-EV</a>.</li>
	<li>{{ trans('datenschutz.policy.13') }} <a href="http://suma-ev.de/" target="_blank">SUMA-EV</a> {{ trans('datenschutz.policy.14') }} <a href="http://www.uni-hannover.de/" target="_blank">{{ trans('datenschutz.policy.15') }}</a> {{ trans('datenschutz.policy.16') }}</li>
	<li>{{ trans('datenschutz.policy.17') }}</li>
	<li>{{ trans('datenschutz.policy.18') }} <a href="http://www.heise.de/newsticker/meldung/Bericht-US-Regierung-zapft-Kundendaten-von-Internet-Firmen-an-1884264.html" target="_blank">{{ trans('datenschutz.policy.19') }}</a> {{ trans('datenschutz.policy.20') }} <a href="http://de.wikipedia.org/wiki/USA_PATRIOT_Act" target="_blank">{{ trans('datenschutz.policy.21') }}</a> {{ trans('datenschutz.policy.22') }}</li></ul>
<h2>{{ trans('datenschutz.twitter') }}</h2>
<pre>@foreach(trans('datenschutz.twitter_quotes') as $quote)
<p>{!! $quote !!}</p>
@endforeach</pre>
@endsection
