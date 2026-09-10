@extends('layouts.subPages')

@section('title', $title )

@section('content')
<h1>{{ trans('admin.check.last_search') }}</h1>
<h2><span class="text-danger">{{ $q }}</span></h2>
<h3>{{ trans('admin.check.repeat_search') }} <a href="{{ action('MetaGerSearch@search') }}?eingabe={{ urlencode($q) }}&encoding=utf8&focus=web&lang=all" target="_blank">MetaGer</a>, oder <a href="https://www.google.de/?gws_rd=ssl#q={{ urlencode($q) }}" target="_blank">Google</a> durchführen.</h3>
@endsection