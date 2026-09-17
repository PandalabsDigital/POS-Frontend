@extends('layouts.app')

@section('title', $meta['title'].' · Reports')

@section('content')
<div class="print-sheet">
@include('intelligence.reports._page', ['heading' => $meta['title'], 'lede' => $meta['purpose']])
<p class="small text-muted"><a href="{{ route('inventory.reports') }}">Open inventory reports</a> for valuation CSV and stock cards.</p>
<div class="row g-3">
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">On-hand value</div><div class="fs-4">@money($stock_value)</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Food sales (tendered)</div><div class="fs-4">@money($sales)</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Ingredient consumption</div><div class="fs-4">@money($consumption)</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Wastage</div><div class="fs-4">@money($waste)</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Purchases</div><div class="fs-4">@money($purchases)</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Food cost</div><div class="fs-4">{{ $food_cost }}%</div><div class="small text-muted">Target {{ $target }}%</div></div></div>
</div>
</div>
@endsection
