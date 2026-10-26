@extends('layouts.app')
@section('title', 'Log in · UCLM Lost & Found')
@section('content')
<section class="auth-grid"><div class="card"><h1>Log in</h1><p class="muted">Welcome back.</p><form method="POST" action="{{ route('login') }}">@csrf
<label for="email">Email address</label><input id="email" name="email" type="email" autocomplete="username" required value="{{ old('email') }}">
<label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required>
@if($errors->any())<div class="error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<label class="checkbox"><input name="remember" type="checkbox" value="1" @checked(old('remember'))> Remember me</label><button class="button full">Log in</button></form><p class="switch">New here? <a href="{{ route('register') }}">Create an account</a></p></div></section>
@endsection

