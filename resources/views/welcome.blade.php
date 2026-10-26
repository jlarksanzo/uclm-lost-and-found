@extends('layouts.app')
@section('content')
<section class="hero">
    <p class="eyebrow">UCLM community</p>
    <h1>Lost. Found.<br><em>Reconnected.</em></h1>
    <p class="lead">A home for lost and found items on campus.</p>
    <div class="actions">
        @auth
        <a class="button" href="{{ route('dashboard') }}">Go to dashboard</a>
        @else
        <a class="button" href="{{ route('register') }}">Create account</a>
        <a class="secondary" href="{{ route('login') }}">Log in</a>
        @endauth
    </div>
</section>
@endsection
