@extends('layouts.app')
@section('title', 'Inventory reports')
@section('content')
@include('inventory._nav')
<div class="d-flex justify-content-between mb-3">
    <h1 class="h3 mb-0">Inventory reports</h1>
    <form class="d-flex gap-2">
        <input type="date" name="from" value="{{ $from }}" class="form-control">
        <input type="date" name="to" value="{{ $to }}" class="form-control">
        <button class="btn btn-accent">Filter</button>
    </form>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-3 stat-card"><div class="stat-label">Valuation</div><div class="fs-4">@money($stock_value)</div><a href="{{ route('inventory.reports.valuation') }}">CSV</a></div>
    <div class="col-md-3 stat-card"><div class="stat-label">Food sales</div><div class="fs-4">@money($sales)</div></div>
    <div class="col-md-3 stat-card"><div class="stat-label">Ingredient cost</div><div class="fs-4">@money($consumption)</div></div>
    <div class="col-md-3 stat-card"><div class="stat-label">Food cost</div><div class="fs-4">{{ $food_cost }}%</div><div class="small text-muted">Target {{ $target }}% · variance {{ round($food_cost - $target, 1) }}</div></div>
</div>
<div class="stat-card mb-4">
    <h2 class="h5">Theoretical vs actual</h2>
    <div class="table-responsive"><table class="table"><thead><tr><th>Item</th><th>Theoretical</th><th>Actual</th><th>Variance</th><th>%</th></tr></thead><tbody>
    @forelse($variance as $row)
        <tr><td>{{ $row->item->name }}</td><td>{{ $row->theoretical }}</td><td>{{ $row->actual }}</td><td>{{ $row->variance }}</td><td>{{ $row->percent }}%</td></tr>
    @empty
        <tr><td colspan="5" class="text-muted">No consumption in this range.</td></tr>
    @endforelse
    </tbody></table></div>
</div>
<div class="stat-card mb-4">
    <h2 class="h5">Purchases</h2>
    <div class="table-responsive"><table class="table"><thead><tr><th>Date</th><th>Supplier</th><th>Invoice</th><th>Total</th></tr></thead><tbody>
    @forelse($purchaseRows as $row)
        <tr><td>{{ $row->received_date?->toDateString() }}</td><td>{{ $row->supplier?->name ?? '—' }}</td><td>{{ $row->invoice_number ?: '—' }}</td><td>@money($row->total)</td></tr>
    @empty
        <tr><td colspan="4" class="text-muted">No purchases in this range.</td></tr>
    @endforelse
    </tbody></table></div>
</div>
<div class="stat-card mb-4">
    <h2 class="h5">Wastage</h2>
    <div class="table-responsive"><table class="table"><thead><tr><th>Item</th><th>Qty</th><th>Reason</th><th>Cost</th></tr></thead><tbody>
    @forelse($wasteRows as $row)
        <tr><td>{{ $row->item->name }}</td><td>{{ $row->quantity }}</td><td>{{ $row->reason }}</td><td>@money($row->cost)</td></tr>
    @empty
        <tr><td colspan="4" class="text-muted">No wastage in this range.</td></tr>
    @endforelse
    </tbody></table></div>
    <p class="small text-muted mb-0">Waste this period: @money($waste)</p>
</div>
<div class="stat-card">
    <h2 class="h5">Inventory summary</h2>
    <div class="table-responsive"><table class="table"><thead><tr><th>Item</th><th>Stock</th><th>Avg cost</th><th>Value</th></tr></thead><tbody>
    @foreach($summary as $item)
        <tr><td>{{ $item->name }}</td><td>{{ $item->onHand() }} {{ $item->stockUnit->code }}</td><td>@money($item->average_cost)</td><td>@money($item->stockValue())</td></tr>
    @endforeach
    </tbody></table></div>
</div>
@endsection
