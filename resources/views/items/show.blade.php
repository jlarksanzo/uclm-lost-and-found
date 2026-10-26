@extends('layouts.app')
@section('title', $report->name.' · UCLM')
@section('content')
<section class="dashboard">
<a href="{{ route('items.index') }}">← Browse items</a>
<div class="page-heading detail-heading"><div><p class="eyebrow">{{ ucfirst($report->type) }} · {{ $report->category }} · {{ ucfirst($report->status) }}</p><h1>{{ $report->name }}</h1></div>
@if($report->user_id===auth()->id() && $report->editable())<a class="secondary" href="{{ route('items.edit',$report) }}">Edit report</a>@endif</div>
@if($errors->any())<div class="error card" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<div class="card detail-card">
@if($report->photo_path)<img class="detail-photo" src="{{ route('items.photo',$report) }}" alt="{{ $report->name }}">@endif
<p class="muted">{{ $report->location }} · {{ $report->occurred_on->format('M j, Y') }}</p><p class="description">{{ $report->description }}</p>
@if($report->user_id===auth()->id() && !$report->editable() && $report->status!=='returned')<p class="help">Editing is locked while ownership claims are being reviewed.</p>@endif
</div>
@if($myClaim)
<div class="card section-gap"><h2>Your claim · {{ ucfirst($myClaim->status) }}</h2><p class="help">Private proof</p><p class="description">{{ $myClaim->proof }}</p>@if($myClaim->review_note)<p class="description">Admin: {{ $myClaim->review_note }}</p>@endif</div>
@elseif($report->status==='approved' && $report->type==='found' && $report->user_id!==auth()->id())
<div class="card section-gap"><h2>Is this yours?</h2><p class="muted">Share proof only you would know. Admins will review your claim.</p><form method="POST" action="{{ route('claims.store',$report) }}">@csrf<label for="proof">Private proof of ownership</label><textarea id="proof" name="proof" minlength="20" maxlength="2000" rows="4" required>{{ old('proof') }}</textarea><p class="help">Describe a hidden detail, contents, or a unique mark. Do not include passwords or ID numbers.</p><button class="button section-gap">Submit claim</button></form></div>
@endif
@if(auth()->user()->is_admin)
<div class="card section-gap"><h2>Admin review</h2><p class="muted">Reported by {{ $report->user->name }}</p>
@if($report->status==='pending')<div class="review-actions">@foreach(['approved'=>'Approve report','rejected'=>'Reject report'] as $value=>$label)<form method="POST" action="{{ route('admin.review',$report) }}">@csrf @method('PATCH')<button class="{{ $value==='approved' ? 'button' : 'secondary' }}" name="status" value="{{ $value }}">{{ $label }}</button></form>@endforeach</div>@endif
<a href="{{ route('admin.claims') }}">Review ownership claims</a>
@if($report->status==='approved')
@if($report->type==='lost' || $approvedClaim)
<h2 class="section-gap">Record physical return</h2><p class="muted">Recipient: {{ $approvedClaim?->user->name ?? $report->user->name }}</p>
<form method="POST" action="{{ route('admin.return',$report) }}">@csrf<label for="note">Handover note</label><textarea id="note" name="note" rows="3" maxlength="1000" required>{{ old('note') }}</textarea><label class="checkbox"><input type="checkbox" name="confirmed" value="1" required> I verified ownership and completed the handover.</label><button class="button section-gap">Record return</button></form>
@else<p class="help">Approve an ownership claim before recording a return.</p>@endif
@endif</div>
@endif
@if($returnRecord)
<div class="card section-gap"><h2>Return completed</h2><p>Returned to {{ $returnRecord->recipient->name }}</p><p class="muted">{{ $returnRecord->created_at->timezone('Asia/Manila')->format('M j, Y · g:i A') }} · Handled by {{ $returnRecord->admin->name }}</p>@if(auth()->user()->is_admin || auth()->id()===$returnRecord->recipient_id)<p class="description">{{ $returnRecord->note }}</p>@endif</div>
@elseif($report->status==='returned')<p class="help">This return predates detailed handover records.</p>@endif
@if($matches->isNotEmpty())
<div class="section-gap"><h2>Possible matches</h2><p class="muted">Based on words, category and location. These are suggestions, not verified ownership.</p><div class="item-grid">@foreach($matches as $match)<a class="card match-card" href="{{ route('items.show',$match) }}"><span class="badge">{{ ucfirst($match->type) }}</span><h2>{{ $match->name }}</h2><p class="muted">{{ $match->location }}</p><p class="help">{{ $match->match_reasons }}</p></a>@endforeach</div></div>
@endif
</section>
@endsection
