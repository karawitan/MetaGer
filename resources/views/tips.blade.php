@extends('layouts.staticPages')

@section('title', $title )

@section('content')
<h1>{{ trans('tips.heading') }}</h1>
<ol>
	@foreach( $tips as $tip )
	<li>{!! $tip !!}</li>
	@endforeach
</ol>
@endsection