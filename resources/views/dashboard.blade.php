@extends('layouts.app')
@section('title', 'Dashboard · UCLM Lost & Found')
@section('content')
<section class="dashboard"><p class="eyebrow">Dashboard</p><h1>Hi, {{ auth()->user()->name }}.</h1><p class="muted">What would you like to do?</p><div class="quick-links"><a class="card" href="{{ route('items.index') }}"><h2>Browse items</h2><p class="muted">View lost and found posts.</p></a><a class="card" href="{{ route('items.create') }}"><h2>Report item</h2><p class="muted">Lost something or found an item?</p></a><a class="card" href="{{ route('items.mine') }}"><h2>My reports</h2><p class="muted">Check your report status.</p></a>@if(auth()->user()->is_admin)<a class="card" href="{{ route('admin.reports') }}"><h2>Manage reports</h2><p class="muted">Review and approve posts.</p></a>@endif</div></section>
@endsection
