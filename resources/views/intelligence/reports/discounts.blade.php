@extends('layouts.app')

@section('title', $meta['title'].' · Reports')

@section('content')
<div class="print-sheet">
@include('intelligence.reports._page', ['heading' => $meta['title'], 'lede' => $meta['purpose']])
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Discount %</div><div class="fs-4">{{ $rate }}%</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Discounted orders</div><div class="fs-4">{{ $count }}</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Discount %</div><div class="fs-4">{{ $avg_employee_discount }}%</div></div></div>
</div>
<p class="small text-muted">Discount codes, promotion names, and manager-vs-cashier roles are not stored. Amount and cashier are recorded. Branch is {{ $filters['restaurant'] }}.</p>
@if($flags->isNotEmpty())
<div class="stat-card mb-4">
    <h2 class="h6 text-danger">Unusual discount activity</h2>
    @foreach($flags as $flag)
        <p class="mb-1">{{ $flag->name }} applied {{ $flag->multiple }}× the average discount ({{ $flag->avg_discount }}% vs {{ $avg_employee_discount }}%).</p>
    @endforeach
</div>
@endif
<div class="row g-4">
    <div class="col-lg-6">
        <div class="stat-card table-responsive">
            <h2 class="h6">By employee</h2>
            <table class="table"><thead><tr><th>Employee</th><th>Discount %</th></tr></thead><tbody>
            @foreach($employees as $row)
                <tr><td>{{ $row->name }}</td><td>{{ $row->avg_discount }}%</td></tr>
            @endforeach
            </tbody></table>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="stat-card table-responsive">
            <h2 class="h6">By product</h2>
            <table class="table"><thead><tr><th>Product</th><th>Discount %</th></tr></thead><tbody>
            @forelse($products as $row)
                <tr><td>{{ $row->name }}</td><td>{{ $row->discount_percent }}%</td></tr>
            @empty
                <tr><td colspan="2" class="text-muted">No discounted lines.</td></tr>
            @endforelse
            </tbody></table>
        </div>
    </div>
</div>
<div class="stat-card table-responsive mt-4">
    <h2 class="h6">Discounted tickets</h2>
    <table class="table"><thead><tr><th>Invoice</th><th>Cashier</th><th>Discount</th><th>Total</th></tr></thead><tbody>
    @forelse($orders as $order)
        <tr>
            <td><a href="{{ route('orders.receipt', $order) }}">{{ $order->invoice_number }}</a></td>
            <td>{{ $order->cashier?->name }}</td>
            <td>{{ $order->discountRate() }}%</td>
            <td>@money($order->grand_total)</td>
        </tr>
    @empty
        <tr><td colspan="4" class="text-muted">No discounted orders.</td></tr>
    @endforelse
    </tbody></table>
</div>
</div>
@endsection
