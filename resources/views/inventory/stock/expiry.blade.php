@extends('layouts.app')
@section('title', 'Expiry')
@section('content')
@include('inventory._nav')
<h1 class="h3 mb-3">Expiry</h1>
<div class="stat-card table-responsive">
<table class="table"><thead><tr><th>Item</th><th>Batch</th><th>Qty</th><th>Expires</th><th>Location</th></tr></thead><tbody>
@forelse($batches as $batch)
    @php
        $days = now()->startOfDay()->diffInDays($batch->expires_at, false);
        $label = $days < 0 ? 'Expired' : ($days === 0 ? 'Today' : ($days <= 3 ? '3 days' : ($days <= 7 ? '7 days' : '30 days')));
    @endphp
<tr>
    <td>{{ $batch->item->name }}</td>
    <td>{{ $batch->batch_number ?: '—' }}</td>
    <td>{{ $batch->quantity }} {{ $batch->item->stockUnit->code }}</td>
    <td>{{ $batch->expires_at->toDateString() }} <span class="badge text-bg-{{ $days < 0 ? 'danger' : 'warning' }}">{{ $label }}</span></td>
    <td>{{ $batch->location->name }}</td>
</tr>
@empty
<tr><td colspan="5" class="text-muted">No tracked batches.</td></tr>
@endforelse
</tbody></table>
</div>
<p class="small text-muted mt-2"><a href="{{ route('inventory.locations.index') }}">Locations</a> · <a href="{{ route('inventory.units.index') }}">Units</a> · <a href="{{ route('inventory.stocktakes.index') }}">Stocktake</a></p>
@endsection
