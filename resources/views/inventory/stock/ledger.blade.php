@extends('layouts.app')
@section('title', 'Stock ledger')
@section('content')
@include('inventory._nav')
<div class="d-flex justify-content-between mb-3"><h1 class="h3 mb-0">Stock ledger</h1><a class="btn btn-outline-secondary" href="{{ route('inventory.ledger.export', request()->query()) }}">Export CSV</a></div>
<form class="row g-2 mb-3">
    <div class="col-md-3"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
    <div class="col-md-3"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
    <div class="col-md-4">
        <select name="item_id" class="form-select">
            <option value="">All items</option>
            @foreach($items as $item)<option value="{{ $item->id }}" @selected(request('item_id')==$item->id)>{{ $item->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
</form>
<div class="stat-card table-responsive">
<table class="table"><thead><tr><th>When</th><th>Item</th><th>Type</th><th>In</th><th>Out</th><th>Balance</th><th>Value</th><th>User</th></tr></thead><tbody>
@foreach($txns as $txn)
<tr>
    <td>{{ $txn->occurred_at?->format('Y-m-d H:i') }}</td>
    <td>{{ $txn->item->name }}</td>
    <td>{{ $txn->type->label() }}</td>
    <td>{{ $txn->quantity_in > 0 ? $txn->quantity_in : '—' }}</td>
    <td>{{ $txn->quantity_out > 0 ? $txn->quantity_out : '—' }}</td>
    <td>{{ $txn->balance_after }} {{ $txn->item->stockUnit->code }}</td>
    <td>@money($txn->total_value)</td>
    <td>{{ $txn->user?->name }}</td>
</tr>
@endforeach
</tbody></table>
{{ $txns->links() }}
</div>
@endsection
