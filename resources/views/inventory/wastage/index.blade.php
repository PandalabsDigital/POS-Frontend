@extends('layouts.app')
@section('title', 'Wastage')
@section('content')
@include('inventory._nav')
<h1 class="h3 mb-1">Wastage</h1>
<p class="text-muted">This month: @money($monthCost)</p>
<div class="row g-3 mb-4">
    <div class="col-md-6 stat-card">
        <h2 class="h6">Top wasted</h2>
        @forelse($top as $row)<div>{{ $row->item?->name }} · @money($row->cost)</div>@empty<div class="text-muted">None</div>@endforelse
    </div>
    <div class="col-md-6 stat-card">
        <h2 class="h6">Reasons</h2>
        @forelse($reasonStats as $row)<div>{{ $reasons[$row->reason] ?? $row->reason }} · @money($row->cost)</div>@empty<div class="text-muted">None</div>@endforelse
    </div>
</div>
<form method="POST" action="{{ route('inventory.wastage.store') }}" class="stat-card mb-4">
    @csrf
    <h2 class="h6">Record wastage</h2>
    <div class="row g-2">
        <div class="col-md-3">
            <select name="inventory_item_id" class="form-select" required>
                <option value="">Item</option>
                @foreach($items as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2"><input name="quantity" type="number" step="0.0001" class="form-control" placeholder="Qty" required></div>
        <div class="col-md-2">
            <select name="unit_id" class="form-select">@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->code }}</option>@endforeach</select>
        </div>
        <div class="col-md-2">
            <select name="inventory_location_id" class="form-select">@foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select>
        </div>
        <div class="col-md-2">
            <select name="reason" class="form-select">@foreach($reasons as $code => $label)<option value="{{ $code }}">{{ $label }}</option>@endforeach</select>
        </div>
        <div class="col-md-1"><button class="btn btn-accent w-100">Save</button></div>
    </div>
</form>
<div class="stat-card table-responsive"><table class="table"><thead><tr><th>When</th><th>Item</th><th>Qty</th><th>Reason</th><th>Cost</th></tr></thead><tbody>
@foreach($rows as $row)
<tr><td>{{ $row->occurred_at?->format('Y-m-d H:i') }}</td><td>{{ $row->item->name }}</td><td>{{ $row->quantity }} {{ $row->unit->code ?? '' }}</td><td>{{ $reasons[$row->reason] ?? $row->reason }}</td><td>@money($row->cost)</td></tr>
@endforeach
</tbody></table>{{ $rows->links() }}</div>
@endsection
