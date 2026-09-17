@extends('layouts.app')

@section('title', $meta['title'].' · Reports')

@section('content')
<div class="print-sheet">
@include('intelligence.reports._page', ['heading' => $meta['title']])
@php
    $sorts = [
        'best_sellers' => ['bi-fire', 'Top sold'],
        'revenue' => ['bi-cash-stack', 'Revenue'],
        'profit' => ['bi-piggy-bank', 'Profit'],
        'margin' => ['bi-percent', 'Margin'],
        'lowest_margin' => ['bi-arrow-down', 'Low margin'],
        'lowest_sellers' => ['bi-hourglass', 'Slow'],
    ];
@endphp
<form class="intel-preset-row mb-3 no-print" method="GET">
    @foreach($filter->query() as $key => $value)
        @if($key !== 'sort')<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
    @endforeach
    @foreach($sorts as $value => [$icon, $label])
        <button class="intel-chip {{ $sort === $value ? 'active' : '' }}" name="sort" value="{{ $value }}">
            <i class="bi {{ $icon }}" aria-hidden="true"></i> {{ $label }}
        </button>
    @endforeach
</form>
<div class="row g-3 mb-4">
    @foreach([
        'star' => ['bi-star-fill', 'Stars'],
        'plow_horse' => ['bi-lightning-fill', 'Busy'],
        'puzzle' => ['bi-gem', 'Gems'],
        'dog' => ['bi-dash-circle-fill', 'Weak'],
    ] as $code => [$icon, $label])
        <div class="col-6 col-lg-3">
            <div class="stat-card intel-mini menu-eng-{{ $code }}">
                <i class="bi {{ $icon }}" aria-hidden="true"></i>
                <strong>{{ $rows->where('classification', $code)->count() }}</strong>
                <span>{{ $label }}</span>
            </div>
        </div>
    @endforeach
</div>
<div class="stat-card table-responsive">
    <table class="table">
        <thead><tr><th>Product</th><th>Category</th><th>Units</th><th>Revenue</th><th>Discounts</th><th>Refunds</th><th>Net</th><th>Food cost</th><th>Profit</th><th>Margin</th><th>Avg price</th><th></th></tr></thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row->name }}</td>
                <td>{{ $row->category }}</td>
                <td>{{ $row->units }}</td>
                <td>@money($row->revenue)</td>
                <td>{{ $row->discount_percent }}%</td>
                <td>@money($row->refunds)</td>
                <td>@money($row->net)</td>
                <td>{{ $row->food_cost === null ? '—' : '' }}@if($row->food_cost !== null)@money($row->food_cost)@endif</td>
                <td>{{ $row->gross_profit === null ? '—' : '' }}@if($row->gross_profit !== null)@money($row->gross_profit)@endif</td>
                <td>{{ $row->margin === null ? '—' : $row->margin.'%' }}</td>
                <td>@money($row->avg_price)</td>
                <td><span class="badge text-bg-light">{{ str_replace('_', ' ', $row->classification) }}</span>
                    <a class="small" href="{{ route('reports.show', array_merge($filter->query(), ['report' => 'sales', 'product_id' => $row->menu_item_id])) }}">Orders</a></td>
            </tr>
        @empty
            <tr><td colspan="12" class="text-muted">No product sales in this range.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</div>
@endsection
