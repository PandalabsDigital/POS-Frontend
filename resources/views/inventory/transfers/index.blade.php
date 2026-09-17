@extends('layouts.app')
@section('title', 'Transfers')
@section('content')
@include('inventory._nav')
<h1 class="h3 mb-3">Transfers</h1>
<form method="POST" action="{{ route('inventory.transfers.store') }}" class="stat-card mb-4">
    @csrf
    <div class="row g-2 mb-2">
        <div class="col-md-4"><select name="from_location_id" class="form-select" required>@foreach($locations as $location)<option value="{{ $location->id }}">From {{ $location->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><select name="to_location_id" class="form-select" required>@foreach($locations as $location)<option value="{{ $location->id }}">To {{ $location->name }}</option>@endforeach</select></div>
    </div>
    <div class="row g-2">
        <div class="col-md-4"><select name="lines[0][inventory_item_id]" class="form-select" required><option value="">Item</option>@foreach($items as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><input name="lines[0][quantity]" type="number" step="0.0001" class="form-control" required></div>
        <div class="col-md-2"><select name="lines[0][unit_id]" class="form-select">@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->code }}</option>@endforeach</select></div>
        <div class="col-md-2"><button class="btn btn-accent">Transfer now</button></div>
    </div>
</form>
<div class="stat-card table-responsive"><table class="table"><thead><tr><th>When</th><th>From</th><th>To</th><th>Lines</th></tr></thead><tbody>
@foreach($transfers as $transfer)
<tr><td>{{ $transfer->completed_at?->format('Y-m-d H:i') }}</td><td>{{ $transfer->fromLocation->name }}</td><td>{{ $transfer->toLocation->name }}</td><td>{{ $transfer->items->count() }}</td></tr>
@endforeach
</tbody></table>{{ $transfers->links() }}</div>
@endsection
