@extends('layouts.app')
@section('title', 'Stock')
@section('content')
@include('inventory._nav')
<div class="d-flex justify-content-between mb-3">
    <h1 class="h3 mb-0">Stock</h1>
    <a href="{{ route('inventory.ledger') }}" class="btn btn-outline-secondary">Ledger</a>
</div>
<form class="row g-2 mb-3">
    <div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Search"></div>
    <div class="col-md-4">
        <select name="location_id" class="form-select">
            <option value="">All locations</option>
            @foreach($locations as $location)
                <option value="{{ $location->id }}" @selected(request('location_id')==$location->id)>{{ $location->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-secondary">Filter</button></div>
</form>
<div class="stat-card table-responsive">
<table class="table"><thead><tr><th>Item</th><th>Location</th><th>Qty</th><th>Min / Par</th><th>Avg cost</th><th>Value</th></tr></thead><tbody>
@foreach($items as $item)
    @forelse($item->balances as $balance)
        <tr>
            <td class="fw-semibold">{{ $item->name }}</td>
            <td>{{ $balance->location->name }}</td>
            <td>{{ $balance->quantity }} {{ $item->stockUnit->code }}</td>
            <td>{{ $balance->minimum_stock ?? $item->minimum_stock }} / {{ $balance->par_level ?? $item->maximum_stock ?? '—' }}</td>
            <td>@money($item->average_cost)</td>
            <td>@money($balance->quantity * $item->average_cost)</td>
        </tr>
    @empty
        <tr><td class="fw-semibold">{{ $item->name }}</td><td>—</td><td>0 {{ $item->stockUnit->code }}</td><td>{{ $item->minimum_stock }} / {{ $item->maximum_stock ?? '—' }}</td><td>@money($item->average_cost)</td><td>@money(0)</td></tr>
    @endforelse
@endforeach
</tbody></table>
{{ $items->links() }}
</div>
@endsection
