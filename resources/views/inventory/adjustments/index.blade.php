@extends('layouts.app')
@section('title', 'Stock adjustments')
@section('content')
@include('inventory._nav')
<h1 class="h3 mb-3">Stock adjustment</h1>
<form method="POST" action="{{ route('inventory.adjustments.store') }}" class="stat-card">
    @csrf
    <div class="row g-3">
        <div class="col-md-4">
            <select name="inventory_item_id" class="form-select" required>
                <option value="">Item</option>
                @foreach($items as $item)<option value="{{ $item->id }}">{{ $item->name }} ({{ $item->stockUnit->code }})</option>@endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="inventory_location_id" class="form-select">@foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select>
        </div>
        <div class="col-md-2"><input name="actual_quantity" type="number" step="0.0001" min="0" class="form-control" placeholder="Physical qty" required></div>
        <div class="col-md-3">
            <select name="reason" class="form-select" required>@foreach($reasons as $code=>$label)<option value="{{ $code }}">{{ $label }}</option>@endforeach</select>
        </div>
        <div class="col-12"><input name="notes" class="form-control" placeholder="Notes"></div>
    </div>
    <button class="btn btn-accent mt-3">Post adjustment</button>
</form>
<p class="small text-muted mt-3">Prefer a full count? Use <a href="{{ route('inventory.stocktakes.index') }}">Stocktake</a>.</p>
@endsection
