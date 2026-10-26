@extends('layouts.app')
@section('title', 'My reports · UCLM')
@section('content')
<section class="dashboard"><div class="page-heading"><h1>My reports</h1><a class="button small" href="{{ route('items.create') }}">Report item</a></div><nav class="tabs" aria-label="My activity"><a href="{{ route('items.mine') }}" aria-current="page">My reports</a><a href="{{ route('claims.mine') }}">My claims</a></nav>@include('items.list')</section>
@endsection
