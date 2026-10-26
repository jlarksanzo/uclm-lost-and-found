<div class="item-grid">
@forelse($reports as $report)
<article class="item-card">
@if($report->photo_path)<img src="{{ route('items.photo',$report) }}" alt="{{ $report->name }}" loading="lazy">@endif
<div class="item-body"><div class="badges"><span class="badge {{ $report->type }}">{{ ucfirst($report->type) }}</span>@if(!request()->routeIs('items.index'))<span class="badge">{{ ucfirst($report->status) }}</span>@endif</div><h2><a href="{{ route('items.show', $report) }}">{{ $report->name }}</a></h2><p class="muted">{{ $report->location }} · {{ $report->occurred_on->format('M j, Y') }}</p><p class="help">{{ $report->category }}</p><p class="description">{{ \Illuminate\Support\Str::limit($report->description, 140) }}</p><a href="{{ route('items.show', $report) }}">View details</a>
@if($adminView ?? false)
<p class="help">Reported by {{ $report->user->name }}</p>
@if($report->status==='pending')<div class="review-actions"><form method="POST" action="{{ route('admin.review',$report) }}">@csrf @method('PATCH')<button class="button small" name="status" value="approved">Approve</button></form><form method="POST" action="{{ route('admin.review',$report) }}">@csrf @method('PATCH')<button class="secondary small" name="status" value="rejected">Reject</button></form></div>@elseif($report->status==='approved')<a class="secondary small" href="{{ route('items.show', $report) }}">Record return</a>@endif
@endif
</div></article>
@empty
<div class="empty-state"><span class="empty-icon" aria-hidden="true">⌕</span><h2>No reports yet</h2><p class="muted">{{ request()->routeIs('items.index') ? 'No approved items match your search.' : 'Reports will appear here.' }}</p></div>
@endforelse
</div>
@if($reports->hasPages())<nav class="pagination" aria-label="Pagination">@if($reports->previousPageUrl())<a class="secondary" href="{{ $reports->previousPageUrl() }}">Previous</a>@endif<span>Page {{ $reports->currentPage() }} of {{ $reports->lastPage() }}</span>@if($reports->nextPageUrl())<a class="secondary" href="{{ $reports->nextPageUrl() }}">Next</a>@endif</nav>@endif
