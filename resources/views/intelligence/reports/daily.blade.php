@extends('layouts.app')

@section('title', $meta['title'].' · Reports')

@section('content')
<div class="print-sheet">
@include('intelligence.reports._page', ['heading' => $meta['title']])
@php $c = $compared['current']; $d = $compared['delta']; @endphp

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card intel-stat">
            <div class="stat-label"><i class="bi bi-cash-stack" aria-hidden="true"></i> Gross sales</div>
            <div class="fs-3">@money($c['gross'])</div>
            @include('intelligence.reports._delta', ['delta' => $d['gross']])
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card intel-stat">
            <div class="stat-label"><i class="bi bi-tag" aria-hidden="true"></i> Discounts</div>
            <div class="fs-3">{{ $c['discount_percent'] }}%</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card intel-stat">
            <div class="stat-label"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Refunds</div>
            <div class="fs-3">@money($c['refunds'])</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card intel-stat intel-stat-net">
            <div class="stat-label"><i class="bi bi-wallet2" aria-hidden="true"></i> Net sales</div>
            <div class="fs-3">@money($c['net'])</div>
            @include('intelligence.reports._delta', ['delta' => $d['net']])
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach([
        ['bi-receipt', 'Orders', $c['orders'], false],
        ['bi-basket', 'Items', $c['items_sold'], false],
        ['bi-receipt-cutoff', 'Avg ticket', $c['aov'], true],
        ['bi-people', 'Guests', $c['customers'], false],
        ['bi-percent', 'Tax', $c['tax'], true],
        ['bi-cash', 'Cash', $c['cash_sales'], true],
        ['bi-credit-card', 'Card', $c['card_sales'], true],
        ['bi-phone', 'UPI', $c['upi_sales'], true],
    ] as [$icon, $label, $value, $money])
        <div class="col-4 col-md-3">
            <div class="stat-card intel-mini">
                <i class="bi {{ $icon }}" aria-hidden="true"></i>
                <strong>{{ $money ? '' : $value }}@if($money)@money($value)@endif</strong>
                <span>{{ $label }}</span>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card intel-compare">
            <i class="bi bi-sun" aria-hidden="true"></i>
            <div>
                <div class="stat-label">vs yesterday</div>
                <strong>@money($today['net'])</strong>
                <div class="text-muted">was @money($yesterday['net'])</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card intel-compare">
            <i class="bi bi-calendar-week" aria-hidden="true"></i>
            <div>
                <div class="stat-label">vs last week</div>
                <strong>@money($today['net'])</strong>
                <div class="text-muted">was @money($weekday['net'])</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card intel-compare">
            <i class="bi bi-calendar3" aria-hidden="true"></i>
            <div>
                <div class="stat-label">vs last year</div>
                <strong>@money($today['net'])</strong>
                <div class="text-muted">was @money($lastYear['net'])</div>
            </div>
        </div>
    </div>
</div>

<details class="stat-card mb-0">
    <summary class="intel-summary"><i class="bi bi-safe" aria-hidden="true"></i> Cash drawer</summary>
    <p class="small text-muted mt-3">Opening float is not recorded, so expected cash cannot be calculated.</p>
    <ul class="list-unstyled mb-0">
        <li class="d-flex justify-content-between py-1"><span>Cash sales</span><strong>@money($c['cash']['sales'])</strong></li>
        <li class="d-flex justify-content-between py-1"><span>Cash refunds</span><strong>@money($c['cash']['refunds'])</strong></li>
        <li class="d-flex justify-content-between py-1 text-muted"><span>Opening / counted / variance</span><span>Not recorded</span></li>
        <li class="d-flex justify-content-between py-1 text-muted"><span>Voids, tips, delivery fees, commissions</span><span>Not recorded</span></li>
    </ul>
</details>
</div>
@endsection
