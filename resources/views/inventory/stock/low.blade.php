@extends('layouts.app')
@section('title', 'Low stock')
@section('content')
@include('inventory._nav')
<div class="d-flex justify-content-between mb-3"><h1 class="h3 mb-0">Low stock</h1><a class="btn btn-accent" href="{{ route('inventory.purchases.create') }}">Add to purchase</a></div>
<div class="stat-card table-responsive">
<table class="table"><thead><tr><th>Item</th><th>Stock</th><th>Min</th><th>Unit</th><th>Suggested</th><th>Supplier</th></tr></thead><tbody>
@forelse($items as $item)
<tr>
    <td class="fw-semibold">{{ $item->name }} @if($item->isOutOfStock())<span class="badge text-bg-danger">Out</span>@else<span class="badge text-bg-warning">Low</span>@endif</td>
    <td>{{ $item->onHand() }}</td>
    <td>{{ $item->minimum_stock }}</td>
    <td>{{ $item->stockUnit->code }}</td>
    <td>{{ $item->suggestedReorderQty() }}</td>
    <td>{{ $item->preferredSupplier?->name ?? '—' }}</td>
</tr>
@empty
<tr><td colspan="6" class="text-muted">All items are above reorder level.</td></tr>
@endforelse
</tbody></table>
</div>
@endsection
