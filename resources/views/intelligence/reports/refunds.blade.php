@extends('layouts.app')

@section('title', $meta['title'].' · Reports')

@section('content')
<div class="print-sheet">
@include('intelligence.reports._page', ['heading' => $meta['title'], 'lede' => $meta['purpose']])
<p class="small text-muted">{{ $compared['current']['unrecorded']['voids'] }} Refund amount is the original ticket total of refunded orders.</p>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Refunded orders</div><div class="fs-4">{{ $rows->count() }}</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Refund amount</div><div class="fs-4">@money($compared['current']['refunds'])</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Voided items / orders</div><div class="text-muted">Not recorded</div></div></div>
</div>
<div class="stat-card table-responsive">
    <table class="table">
        <thead><tr><th>Invoice</th><th>Cashier</th><th>Payment</th><th>Amount</th><th>Completed</th><th>Items</th></tr></thead>
        <tbody>
        @forelse($rows as $order)
            <tr>
                <td><a href="{{ route('orders.receipt', $order) }}">{{ $order->invoice_number }}</a></td>
                <td>{{ $order->cashier?->name }}</td>
                <td>{{ config('taxation.payment_methods.'.$order->payment_method, $order->payment_method ?: '—') }}</td>
                <td>@money($order->grand_total)</td>
                <td>{{ $order->completed_at?->format('Y-m-d H:i') }}</td>
                <td>{{ $order->items->sum('quantity') }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-muted">No refunds in this range.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</div>
@endsection
