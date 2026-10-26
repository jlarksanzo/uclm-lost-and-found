@extends('layouts.app')
@section('title', ($adminView ? 'Review claims' : 'My claims').' · UCLM')
@section('content')
<section class="dashboard"><h1>{{ $adminView ? 'Review claims' : 'My claims' }}</h1>
@if($adminView)@include('admin-nav')<nav class="tabs" aria-label="Claim status">@foreach(['pending','approved','rejected'] as $filter)<a href="{{ route('admin.claims',['status'=>$filter]) }}" @if($status===$filter) aria-current="page" @endif>{{ ucfirst($filter) }}</a>@endforeach</nav>@else<nav class="tabs" aria-label="My activity"><a href="{{ route('items.mine') }}">My reports</a><a href="{{ route('claims.mine') }}" aria-current="page">My claims</a></nav>@endif
@if($errors->any())<div class="error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@forelse($claims as $claim)
<article class="card section-gap"><span class="badge">{{ ucfirst($claim->status) }}</span><h2 class="section-gap">{{ $claim->report->name }}</h2>
@if($adminView)<p class="muted">Claimant: {{ $claim->user->name }} · {{ $claim->created_at->timezone('Asia/Manila')->format('M j, Y') }}</p>@endif
<p class="help">Private proof of ownership</p><p class="description">{{ $claim->proof }}</p>
@if($claim->review_note)<p class="description">Admin note: {{ $claim->review_note }}</p>@endif
@if($claim->report->status==='approved' || $claim->report->visibleTo(auth()->user()))<a href="{{ route('items.show',$claim->report) }}">View item</a>@endif
@if($adminView && $claim->status==='pending' && $claim->report->status==='approved' && $claim->user_id!==auth()->id())
<form method="POST" action="{{ route('admin.claims.review',$claim) }}">@csrf @method('PATCH')<label for="review-{{ $claim->id }}">Review note</label><textarea id="review-{{ $claim->id }}" name="review_note" rows="2" maxlength="1000" required></textarea><p class="help">Verify ownership before approval. The claimant can read this note.</p><div class="review-actions section-gap"><button class="button" name="status" value="approved">Approve claim</button><button class="secondary" name="status" value="rejected">Reject claim</button></div></form>
@elseif($adminView && $claim->user_id===auth()->id())<p class="help">Another admin must review your claim.</p>@endif
</article>
@empty<div class="empty-state"><h2>No claims yet</h2><p class="muted">Claims appear here after submission.</p></div>@endforelse
@include('pagination', ['page'=>$claims])
</section>
@endsection
