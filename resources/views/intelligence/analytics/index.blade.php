@extends('layouts.app')

@section('title', 'Analytics')

@section('content')
@php
    $c = $compared['current'];
    $icons = [
        'positive' => 'bi-arrow-up-circle-fill',
        'warning' => 'bi-exclamation-triangle-fill',
        'alert' => 'bi-exclamation-octagon-fill',
        'info' => 'bi-lightbulb-fill',
    ];
    $eng = [
        'star' => ['Stars', 'bi-star-fill', 'Sell more of these'],
        'plow_horse' => ['Busy, thin profit', 'bi-lightning-fill', 'Fix cost or price'],
        'puzzle' => ['Hidden gems', 'bi-gem', 'Promote these'],
        'dog' => ['Weak items', 'bi-dash-circle-fill', 'Cut or change'],
    ];
@endphp
@include('intelligence.reports._nav')
<div class="intel-hero mb-3">
    <span class="intel-page-icon" aria-hidden="true"><i class="bi bi-lightbulb-fill"></i></span>
    <div>
        <h1 class="h3 mb-1">Analytics</h1>
        <p class="text-muted mb-0">{{ $filter->from->format('d M') }} – {{ $filter->to->format('d M Y') }} · what to do next</p>
    </div>
</div>
@include('intelligence.reports._filters')

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card intel-mini">
            <i class="bi bi-wallet2" aria-hidden="true"></i>
            <strong>@money($c['net'])</strong>
            <span>Net sales</span>
            @include('intelligence.reports._delta', ['delta' => $compared['delta']['net']])
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card intel-mini">
            <i class="bi bi-receipt" aria-hidden="true"></i>
            <strong>{{ $c['orders'] }}</strong>
            <span>Orders</span>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card intel-mini">
            <i class="bi bi-receipt-cutoff" aria-hidden="true"></i>
            <strong>@money($c['aov'])</strong>
            <span>Avg ticket</span>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card intel-mini">
            <i class="bi bi-tag-fill" aria-hidden="true"></i>
            <strong>{{ $c['discount_percent'] }}%</strong>
            <span>Discounts</span>
        </div>
    </div>
</div>

<h2 class="h6 mb-3"><i class="bi bi-signpost-2-fill text-accent" aria-hidden="true"></i> Do this next</h2>
<div class="intel-actions mb-4">
    @foreach($insights as $insight)
        <div class="stat-card insight-card insight-{{ $insight['severity'] }}">
            <i class="bi {{ $icons[$insight['severity']] ?? 'bi-info-circle-fill' }}" aria-hidden="true"></i>
            <div>
                <strong>{{ $insight['title'] }}</strong>
                <p class="mb-0">{{ $insight['action'] }}</p>
            </div>
        </div>
    @endforeach
</div>

<h2 class="h6 mb-3"><i class="bi bi-fork-knife text-accent" aria-hidden="true"></i> Menu</h2>
<div class="row g-3 mb-4">
    @foreach($eng as $code => [$label, $icon, $tip])
        <div class="col-6 col-lg-3">
            <div class="stat-card menu-eng-{{ $code }} intel-eng">
                <i class="bi {{ $icon }}" aria-hidden="true"></i>
                <strong>{{ $label }}</strong>
                <span class="small text-muted">{{ $tip }}</span>
                @forelse($engineering[$code]->take(3) as $item)
                    <div class="intel-eng-item">{{ $item->name }}</div>
                @empty
                    <div class="text-muted small">None</div>
                @endforelse
            </div>
        </div>
    @endforeach
</div>

<div class="stat-card">
    <h2 class="h6"><i class="bi bi-graph-up" aria-hidden="true"></i> Sales by day</h2>
    <canvas id="aDaily" height="90"></canvas>
</div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('aDaily'), {
    type: 'line',
    data: { labels: @json($daily->pluck('bucket')), datasets: [{ data: @json($daily->pluck('net')), borderColor: '#ff6a00', fill: true, backgroundColor: 'rgba(255,106,0,.16)', tension: .35 }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>
@endpush
