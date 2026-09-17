@extends('layouts.app')
@section('title', 'Purchase '.$purchase->id)
@section('content')
@include('inventory._nav')
<h1 class="h3 mb-3">Purchase {{ $purchase->invoice_number ?: '#'.$purchase->id }}</h1>
<p>{{ $purchase->supplier?->name }} · {{ $purchase->received_date?->toDateString() }} · @money($purchase->total)</p>
<div class="stat-card table-responsive"><table class="table">
<thead><tr><th>Item</th><th>Qty</th><th>Cost</th><th>Total</th></tr></thead>
<tbody>
@foreach($purchase->items as $line)
<tr><td>{{ $line->item->name }}</td><td>{{ $line->quantity }} {{ $line->unit->code }}</td><td>@money($line->unit_cost)</td><td>@money($line->line_total)</td></tr>
@endforeach
</tbody></table></div>
@endsection
