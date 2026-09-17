@extends('layouts.app')
@section('title', 'Stocktake '.$stocktake->id)
@section('content')
@include('inventory._nav')
<h1 class="h3 mb-3">Count — {{ $stocktake->location->name }}</h1>
@if($stocktake->status === 'draft')
<form method="POST" action="{{ route('inventory.stocktakes.update', $stocktake) }}">
    @csrf @method('PUT')
    <div class="stat-card table-responsive mb-3">
        <table class="table"><thead><tr><th>Item</th><th>Expected</th><th>Counted</th><th>Diff</th></tr></thead><tbody>
        @foreach($stocktake->items as $line)
        <tr>
            <td>{{ $line->item->name }}</td>
            <td>{{ $line->expected_quantity }} {{ $line->item->stockUnit->code }}</td>
            <td><input name="counts[{{ $line->id }}]" type="number" step="0.0001" class="form-control" value="{{ $line->counted_quantity }}"></td>
            <td>{{ $line->difference }}</td>
        </tr>
        @endforeach
        </tbody></table>
    </div>
    <button class="btn btn-outline-secondary">Save counts</button>
</form>
<form method="POST" action="{{ route('inventory.stocktakes.confirm', $stocktake) }}" class="mt-2" onsubmit="return confirm('Post adjustments for entered counts?')">
    @csrf
    <button class="btn btn-accent">Confirm stocktake</button>
</form>
@else
<p>Confirmed {{ $stocktake->confirmed_at }}</p>
@endif
@endsection
