@extends('layouts.app')
@section('title', 'Notifications · UCLM')
@section('content')
<section class="dashboard"><div class="page-heading"><h1>Notifications</h1><form method="POST" action="{{ route('notifications.read') }}">@csrf<button class="secondary small">Mark all read</button></form></div>
@forelse($notifications as $notice)<article class="card section-gap {{ $notice->read_at ? '' : 'unread' }}"><p>{{ $notice->data['message'] }}</p><p class="help">{{ $notice->created_at->timezone('Asia/Manila')->format('M j, Y · g:i A') }}{{ $notice->read_at ? '' : ' · Unread' }}</p><a href="{{ $notice->data['url'] }}">View</a></article>@empty<div class="empty-state"><h2>You’re all caught up</h2></div>@endforelse
@include('pagination', ['page'=>$notifications])
</section>
@endsection
