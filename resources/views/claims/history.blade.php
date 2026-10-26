@extends('layouts.app')
@section('title', 'Return history · UCLM')
@section('content')
<section class="dashboard"><h1>Return history</h1>@include('admin-nav')<p class="muted">Completed handovers recorded by admins.</p>
@forelse($records as $record)<article class="card section-gap"><h2><a href="{{ route('items.show',$record->report) }}">{{ $record->report->name }}</a></h2><p>Returned to {{ $record->recipient->name }}</p><p class="muted">{{ $record->created_at->timezone('Asia/Manila')->format('M j, Y · g:i A') }} · Handled by {{ $record->admin->name }}</p><p class="description">{{ $record->note }}</p></article>@empty<div class="empty-state"><h2>No returns recorded</h2></div>@endforelse
@include('pagination', ['page'=>$records])
</section>
@endsection
