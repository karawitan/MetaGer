@extends('layouts.subPages')

@section('title', $title )

@section('navbarFocus.donate', 'class="dropdown active"')

@section('content')
<h1>{{ trans('spendenaufruf.heading') }}</h1>
<p>
{!! trans('spendenaufruf.p1') !!}
</p>
<p>
{{ trans('spendenaufruf.p2') }}
</p>
<p>
{{ trans('spendenaufruf.p3') }}
</p>
<h3>
{{ trans('spendenaufruf.h3_1') }}
</h3>
<p><a href="{{ LaravelLocalization::getLocalizedURL(LaravelLocalization::getCurrentLocale(), "/spende") }}">{{ LaravelLocalization::getLocalizedURL(LaravelLocalization::getCurrentLocale(), "/spende") }}</a></p>
<p>
{{ trans('spendenaufruf.p4') }}
</p>
<p>
{{ trans('spendenaufruf.p5') }}
</p>
<p>
{{ trans('spendenaufruf.p6') }}
</p>
<p>
{{ trans('spendenaufruf.p7') }}
<br />
</p>
<h3>{{ trans('spendenaufruf.h3_2') }}</h3>
<div class="">
	<div class="col-sm-6">
		<h2>{{ trans('spenden.bankinfo.1') }}</h2>
		<p style="white-space:pre;">{{ trans('spenden.bankinfo.2') }}</p>
		<p class="text-muted">{{ trans('spenden.bankinfo.3') }}</p>
	</div>
	<div class="col-sm-6">
	</div>
	</div>
	<div class="clearfix"></div>
	<hr />
	<div class="col-md-6">
		<h2 id="lastschrift">{{ trans('spenden.lastschrift.1') }}</h2>
		<p>{{ trans('spenden.lastschrift.2') }}</p>
		<form role="form" method="POST" action="{{ action('MailController@donation') }}">
			{{ csrf_field() }}
			<div class="form-group" style="text-align:left;">
				<label for="Name">{{ trans('spenden.lastschrift.3') }}</label>
				<input type="text" class="form-control" id="Name" required="" name="Name" placeholder="{{ trans('spenden.lastschrift.3.placeholder') }}">
			</div>
			<div class="form-group" style="text-align:left;">
				<label for="email">{{ trans('spenden.lastschrift.4') }}</label>
				<input type="email" class="form-control" id="email" name="email" placeholder="Email">
			</div>
			<div class="form-group" style="text-align:left;">
				<label for="tel">{{ trans('spenden.lastschrift.5') }}</label>
				<input type="tel" class="form-control" id="tel" name="Telefon" placeholder="xxxx-xxxxx">
			</div>
			<div class="form-group" style="text-align:left;">
				<label for="iban">{{ trans('spenden.lastschrift.6') }}</label>
				<input type="text" class="form-control" id="iban" required="" name="Kontonummer" placeholder="IBAN">
			</div>
			<div class="form-group" style="text-align:left;">
				<label for="bic">{{ trans('spenden.lastschrift.7') }}</label>
				<input type="text" class="form-control" id="bic" required="" name="Bankleitzahl" placeholder="BIC">
			</div>
			<div class="form-group" style="text-align:left;">
				<label for="msg">{{ trans('spenden.lastschrift.8') }}</label>
				<textarea class="form-control" id="msg" required="" name="Nachricht" placeholder="{{ trans('spenden.lastschrift.8.placeholder') }}"></textarea>
			</div>
			<button type="submit" class="btn btn-default">{{ trans('spenden.lastschrift.9') }}</button>
		</form>
		<p>{{ trans('spenden.lastschrift.10') }}</p>
	</div>
	<div class="col-md-6">
		<h2 id="mails">{{ trans('spendenaufruf.emails_heading') }}</h2>
		<ul style="text-align:left; list-style-type: initial;">
			@foreach(trans('spendenaufruf.testimonials') as $testimonial)
			<li>{{ $testimonial }}</li>
			@endforeach
		</ul>
	</div>
</div>
<div id="left" class="col-lg-6 col-md-12 col-sm-12 others">
		
		
	</div>
@endsection
