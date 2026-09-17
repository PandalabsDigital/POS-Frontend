@php
    $meta = $meta ?? [];
@endphp
<div class="intel-page-head mb-3">
    <div class="d-flex align-items-center gap-3 min-w-0">
        @if(!empty($meta['icon']))
            <span class="intel-page-icon" aria-hidden="true"><i class="bi {{ $meta['icon'] }}"></i></span>
        @endif
        <div class="min-w-0">
            <h1 class="h3 mb-0">{{ $heading }}</h1>
            <p class="text-muted mb-0">{{ $filter->from->format('d M') }} – {{ $filter->to->format('d M Y') }}</p>
        </div>
    </div>
</div>
@include('intelligence.reports._nav')
@include('intelligence.reports._filters')
