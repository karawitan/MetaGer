@extends('layouts.subPages')

@section('title', $title )

@section('content')
<h2>{{ exec("uptime") }}</h2>
<table class="table table-striped">
	<caption>{{ trans('admin.count.caption') }}</caption>
	<tr>
		<th>{{ trans('admin.count.date') }}</th>
		<th>{{ trans('admin.count.same_time') }}</th>
		<th>{{ trans('admin.count.total') }}</th>
		<th>{{ trans('admin.count.mean') }}</th>
	</tr>
	@if( isset($today) )
	<tr>
		<td>{{ date("D, d M y", mktime(date("H"),date("i"), date("s"), date("m"), date("d"), date("Y"))) }}</td>
		<td>{{ $today }}</td>
		<td>???</td>
		<td>???</td>
	</tr>
	@endif
	@foreach($oldLogs as $key => $value)
	<tr>
		<td>{{ date("D, d M y", mktime(date("H"),date("i"), date("s"), date("m"), date("d")-$key, date("Y"))) }}</td>
		<td>{{ $value['sameTime'] }}</td>
		<td>{{ $value['insgesamt'] }}</td>
		<td>{{ $value['median'] }}</td>
	</tr>
	@endforeach
</table>

@if( isset($rekordDate) && isset($rekordTagSameTime) && isset($rekordCount) )
<h3>{!! trans('admin.count.record', ['date' => $rekordDate, 'same_time' => $rekordTagSameTime, 'total' => $rekordCount]) !!}</h3>
@endif
@endsection