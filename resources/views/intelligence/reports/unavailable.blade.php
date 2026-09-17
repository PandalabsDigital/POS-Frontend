@extends('layouts.app')

@section('title', $meta['title'].' · Reports')

@section('content')
<div class="print-sheet">
@include('intelligence.reports._page', ['heading' => $meta['title'], 'lede' => $meta['purpose']])
<div class="stat-card intel-hero">
    <span class="intel-page-icon"><i class="bi {{ $report === 'expenses' ? 'bi-wallet2' : 'bi-grid-3x3-gap' }}" aria-hidden="true"></i></span>
    <div>
        <h2 class="h5 mb-1">Not in this POS yet</h2>
        @if($report === 'expenses')
            <p class="mb-0">Use <a href="{{ route('reports.show', array_merge($filter->query(), ['report' => 'purchases'])) }}">Buys</a> and <a href="{{ route('reports.show', array_merge($filter->query(), ['report' => 'wastage'])) }}">Waste</a> for stock costs.</p>
        @else
            <p class="mb-0">Filter <a href="{{ route('reports.show', array_merge($filter->query(), ['report' => 'sales'])) }}">Sales</a> to Dine-in instead.</p>
        @endif
    </div>
</div>
</div>
@endsection
