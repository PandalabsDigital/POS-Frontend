@extends('layouts.app')

@section('title', $meta['title'].' · Reports')

@section('content')
<div class="print-sheet">
@include('intelligence.reports._page', ['heading' => $meta['title'], 'lede' => $meta['purpose']])
@php $c = $compared['current']; @endphp
<p class="small text-muted">Gross profit uses recipe portion cost × units sold for items that have a sale recipe. {{ $uncosted }} sold items have no recipe, so they are excluded from margin instead of treating cost as zero. Purchases are cash spent on stock, not the same as COGS.</p>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Net sales</div><div class="fs-4">@money($c['net'])</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Costed product net</div><div class="fs-4">@money($costed_net)</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Recipe food cost</div><div class="fs-4">@money($recipe_cost)</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Gross profit (costed)</div><div class="fs-4">@money($gross_profit)</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Gross margin</div><div class="fs-4">{{ $margin === null ? '—' : $margin.'%' }}</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Inventory consumption</div><div class="fs-4">@money($consumption)</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Wastage</div><div class="fs-4">@money($waste)</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Purchases</div><div class="fs-4">@money($purchases)</div></div></div>
</div>
</div>
@endsection
