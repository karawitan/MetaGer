@extends('layouts.subPages')

@section('title', $title )

@section('navbarFocus.donate', 'class="dropdown active"')

@section('content')
<h1>{{ trans('beitritt.heading') }}</h1>
<form>
	<div class="form-group">
		<label for="name" class="non-bold">{{ trans('beitritt.declaration') }}</label>
		<input type="text" class="form-control" name="name" placeholder="{{ trans('beitritt.name_placeholder') }}" required/>
	</div>
	<div class="form-group">
		<label for="firma" class="non-bold">{{ trans('beitritt.company') }}</label>
		<input type="text" class="form-control" name="firma" placeholder="{{ trans('beitritt.company_placeholder') }}" />
	</div>
	<div class="form-group">
		<label for="funktion" class="non-bold">{{ trans('beitritt.function') }}</label>
		<input type="text" class="form-control" name="funktion" placeholder="{{ trans('beitritt.function_placeholder') }}" />
	</div>
	<div class="form-group">
		<label for="adresse" class="non-bold">{{ trans('beitritt.address') }}</label>
		<input type="text" class="form-control" name="adresse" placeholder="{{ trans('beitritt.address_placeholder') }}" required/>
	</div>
	<div class="form-group">
		<label for="email" class="non-bold">{{ trans('beitritt.email') }}</label>
		<input type="email" class="form-control" name="email" placeholder=""/>
	</div>
	<div class="form-group">
		<label for="homepage" class="non-bold">{{ trans('beitritt.homepage') }}</label>
		<input type="text" class="form-control" name="homepage" placeholder="http://"/>
	</div>
	<div class="form-group">
		<label for="telefon" class="non-bold">{{ trans('beitritt.phone') }}</label>
		<input type="text" class="form-control" name="telefon" placeholder="{{ trans('beitritt.phone_placeholder') }}"/>
	</div>
	<div class="form-group">
		<label class="non-bold" for="betrag">{{ trans('beitritt.membership_text') }}</label>
		<div class="row">
			<div class="col-xs-2">
				<input type="text" class="form-control" name="betrag" />
			</div>
			<div class="col-xs-2">
				<p class="help-block">{{ trans('beitritt.per_month') }}</p>
			</div>
		</div>
	</div>
	<label class="non-bold">
		{{ trans('beitritt.annual_note') }}
	</label>
	<label class="non-bold">
		{{ trans('beitritt.publish_consent') }}
	</label>
	<div class="row">
		<div class="col-xs-2">
			<div class="radio">
				<label>
					<input type="radio" name="veröffentlichung" checked> {{ trans('beitritt.yes') }}
				</label>
			</div>
		</div>
		<div class="col-xs-2">
			<div class="radio">
				<label>
					<input type="radio" name="veröffentlichung"> {{ trans('beitritt.no') }}
				</label>
			</div>
		</div>
	</div>
	<div class="form-group">
		<label for="ort">{{ trans('beitritt.place_date') }}</label>
		<input type="text" class="form-control" id="ort" placeholder=""/>
	</div>
	<br />

	<p class="sign">
	---------------------------------------------------------<br />
	{{ trans('beitritt.signature') }}
	</p>
	<h3>{{ trans('beitritt.debit_heading') }}</h3>
	<p>{{ trans('beitritt.debit_text') }}</p>
	<div class="form-group">
		<label for="kontoname" class="non-bold">{{ trans('beitritt.account_holder') }}</label>
		<input type="text" class="form-control" name="kontoname" placeholder=""/>
	</div>
	<div class="form-group">
		<label for="bankverbindung" class="non-bold">{{ trans('beitritt.bank') }}</label>
		<input type="text" class="form-control" name="bankverbindung" placeholder=""/>
	</div>
	<div class="form-group">
		<label for="iban" class="non-bold">{{ trans('beitritt.iban') }}</label>
		<input type="text" class="form-control" name="iban" placeholder=""/>
	</div>
	<div class="form-group">
		<label for="bic" class="non-bold">{{ trans('beitritt.bic') }}</label>
		<input type="text" class="form-control" name="bic" placeholder=""/>
	</div>
	<div class="form-group">
		<label for="ort2" class="non-bold">{{ trans('beitritt.place_date') }}</label>
		<input type="text" class="form-control" id="ort2" placeholder=""/>
	</div>
	<br />
	<p class="sign">
	---------------------------------------------------------<br />
	{{ trans('beitritt.signature') }}
	</p>
</form>
<hr />
<p>{{ trans('beitritt.print_instructions') }}</p>
<ul>
<li>{{ trans('beitritt.fax_option') }}</li>
<li>{{ trans('beitritt.mail_option') }}</li>
<li>{{ trans('beitritt.scan_option') }}</li>
</ul>
<p>{{ trans('beitritt.notify_note') }}</p>
<p>{{ trans('beitritt.tax_note') }}</p>
<button type="button" class="btn btn-lg btn-primary noprint" onclick="window.print();">{{ trans('beitritt.print_button') }}</button>
@endsection
