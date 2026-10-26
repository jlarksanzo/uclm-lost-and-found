@extends('layouts.app')
@section('title', 'Manage reports · UCLM')
@section('content')
<section class="dashboard"><h1>Manage reports</h1>@include('admin-nav')<nav class="tabs" aria-label="Report status">@foreach(['pending','approved','rejected','returned'] as $filter)<a href="{{ route('admin.reports',['status'=>$filter]) }}" @if($status===$filter) aria-current="page" @endif>{{ ucfirst($filter) }}</a>@endforeach</nav>@include('items.list', ['adminView'=>true])</section>
@endsection
