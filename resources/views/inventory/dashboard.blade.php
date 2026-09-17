@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
@include('inventory._nav')
<div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Inventory</h1>
        <p class="text-muted mb-0">Stock, purchases, recipes, waste, and food cost — without an ERP.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-accent" href="{{ route('inventory.items.create') }}">Add item</a>
        <a class="btn btn-outline-secondary" href="{{ route('inventory.purchases.create') }}">Receive stock</a>
        <a class="btn btn-outline-secondary" href="{{ route('inventory.wastage.index') }}">Record wastage</a>
        <a class="btn btn-outline-secondary" href="{{ route('inventory.stocktakes.index') }}">Stocktake</a>
        <a class="btn btn-outline-secondary" href="{{ route('inventory.transfers.index') }}">Transfer</a>
        <a class="btn btn-outline-secondary" href="{{ route('inventory.suppliers.create') }}">Add supplier</a>
        <a class="btn btn-outline-secondary" href="{{ route('inventory.recipes.create') }}">Create recipe</a>
    </div>
</div>

@if($settings['low_stock_alerts'] && ($stats['low_stock_count'] || $stats['out_of_stock_count'] || $stats['expiring_count'] || $expired))
    <div class="alert alert-warning">
        @if($stats['out_of_stock_count']) {{ $stats['out_of_stock_count'] }} out of stock. @endif
        @if($stats['low_stock_count']) {{ $stats['low_stock_count'] }} low stock. @endif
        @if($stats['expiring_count']) {{ $stats['expiring_count'] }} expiring soon. @endif
        @if($expired) {{ $expired }} expired batches. @endif
        <a href="{{ route('inventory.low') }}">Review</a>
    </div>
@endif
<div class="row g-3 mb-4">
    @foreach([
        ['Stock value', money($stats['stock_value'])],
        ['Low stock', $stats['low_stock_count']],
        ['Out of stock', $stats['out_of_stock_count']],
        ['Expiring soon', $stats['expiring_count']],
        ["Today's purchases", money($stats['today_purchases'])],
        ["Today's wastage", money($stats['today_wastage'])],
        ['Food cost', $stats['food_cost'].'% (target '.$stats['food_cost_target'].'%)'],
        ['Turnover', $stats['turnover']],
    ] as [$label, $value])
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">{{ $label }}</div>
                <div class="fs-4 fw-bold">{{ $value }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="stat-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Low stock</h2>
        <a href="{{ route('inventory.purchases.create') }}" class="btn btn-sm btn-accent">Create purchase</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Item</th><th>Stock</th><th>Min</th><th>Unit</th><th>Reorder</th><th>Supplier</th></tr></thead>
            <tbody>
            @forelse($stats['low_stock'] as $item)
                <tr>
                    <td class="fw-semibold">{{ $item->name }}</td>
                    <td>{{ $item->onHand() }}</td>
                    <td>{{ $item->minimum_stock }}</td>
                    <td>{{ $item->stockUnit->code }}</td>
                    <td>{{ $item->suggestedReorderQty() }}</td>
                    <td>{{ $item->preferredSupplier?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">Nothing is below reorder level.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
