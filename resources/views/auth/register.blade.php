@extends('layouts.app')
@section('title', 'Create account · UCLM Lost & Found')
@section('content')
<section class="auth-grid"><div class="card"><h1>Create account</h1><p class="muted">Join UCLM Lost &amp; Found.</p><form method="POST" action="{{ route('register') }}">@csrf
<label for="name">Full name</label><input id="name" name="name" autocomplete="name" maxlength="100" required value="{{ old('name') }}">
<label for="email">Email address</label><input id="email" name="email" type="email" autocomplete="username" maxlength="254" required value="{{ old('email') }}">
<label for="password">Password</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required aria-describedby="password-help"><p class="help" id="password-help">Use at least 8 characters.</p>
<label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
@if($errors->any())<div class="error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<button class="button full">Create account</button></form><p class="switch">Already registered? <a href="{{ route('login') }}">Log in</a></p></div></section>
@endsection

