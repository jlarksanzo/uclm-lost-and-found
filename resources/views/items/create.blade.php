@extends('layouts.app')
@section('title', 'Report item · UCLM')
@section('content')
<section class="auth-grid report-form"><div class="card"><h1>Report item</h1><p class="muted">Posts appear after admin review.</p>
<form method="POST" action="{{ route('items.store') }}" enctype="multipart/form-data">@csrf
<label for="type">Report type</label><select id="type" name="type" required><option value="lost" @selected(old('type')==='lost')>Lost item</option><option value="found" @selected(old('type')==='found')>Found item</option></select>
<label for="name">Item name</label><input id="name" name="name" required maxlength="100" value="{{ old('name') }}" placeholder="e.g. Blue umbrella">
<label for="description">Description</label><textarea id="description" name="description" required maxlength="2000" rows="3">{{ old('description') }}</textarea><p class="help">Keep identifying details private to help verify ownership.</p>
<label for="location">Location</label><input id="location" name="location" required maxlength="150" value="{{ old('location') }}" placeholder="e.g. Library">
<label for="occurred_on">Date lost or found</label><input id="occurred_on" name="occurred_on" type="date" required max="{{ now()->toDateString() }}" value="{{ old('occurred_on', now()->toDateString()) }}">
<label for="photo">Photo <span class="muted">(optional)</span></label><input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"><p class="help">JPG, PNG or WebP · Up to 2 MB. Avoid IDs or personal information.</p>
@if($errors->any())<div class="error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<button class="button full">Submit report</button></form></div></section>
@endsection
