@extends('layouts.app')
@section('title', 'My reports · UCLM')
@section('content')
<section class="dashboard"><div class="page-heading"><h1>My reports</h1><a class="button small" href="{{ route('items.create') }}">Report item</a></div>@include('items.list')</section>
@endsection
