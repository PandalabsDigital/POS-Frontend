@extends('layouts.app')
@section('title', 'Purchases')
@section('content')
@include('inventory._nav')
<div class="d-flex justify-content-between mb-3"><h1 class="h3 mb-0">Purchases</h1><a class="btn btn-accent" href="{{ route('inventory.purchases.create') }}">Quick purchase</a></div>
<div class="stat-card table-responsive">
<table class="table"><thead><tr><th>Date</th><th>Supplier</th><th>Invoice</th><th>Total</th><th></th></tr></thead><tbody>
@forelse($purchases as $purchase)
<tr>
    <td>{{ $purchase->received_date?->toDateString() }}</td>
    <td>{{ $purchase->supplier?->name ?? '—' }}</td>
    <td>{{ $purchase->invoice_number ?: '—' }}</td>
    <td>@money($purchase->total)</td>
    <td><a href="{{ route('inventory.purchases.show', $purchase) }}">View</a></td>
</tr>
@empty
<tr><td colspan="5" class="text-muted">No purchases yet.</td></tr>
@endforelse
</tbody></table>{{ $purchases->links() }}
</div>
@endsection
