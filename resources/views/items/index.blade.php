@extends('layouts.app')
@section('title', 'Browse items · UCLM')
@section('content')
<section class="dashboard"><div class="page-heading"><h1>Browse items</h1><a class="button small" href="{{ route('items.create') }}">Report item</a></div>
<form class="filters" method="GET" action="{{ route('items.index') }}"><label class="sr-only" for="q">Search items or locations</label><input id="q" name="q" placeholder="Search items or locations" maxlength="100" value="{{ request('q') }}"><label class="sr-only" for="type">Report type</label><select name="type" id="type"><option value="">All items</option><option value="lost" @selected(request('type')==='lost')>Lost</option><option value="found" @selected(request('type')==='found')>Found</option></select><button class="button">Search</button><a href="{{ route('items.index') }}">Reset</a></form>
@if($errors->any())<p class="error" role="alert">Choose a valid filter.</p>@endif
@include('items.list')
</section>
@endsection
