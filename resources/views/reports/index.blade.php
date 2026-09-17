@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <h1 class="h3 mb-0">Reports</h1>
    <div class="d-flex flex-column flex-sm-row gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('taxes.reports') }}">Tax reports</a>
        <form class="d-flex flex-column flex-sm-row gap-2">
        <input type="date" name="from" class="form-control" value="{{ $from }}">
        <input type="date" name="to" class="form-control" value="{{ $to }}">
        <button class="btn btn-accent">Filter</button>
        </form>
    </div>
</div>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="stat-card">
            <h2 class="h5">Daily sales</h2>
            <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Date</th><th>Orders</th><th>Revenue</th></tr></thead>
                <tbody>
                @forelse($daily as $row)
                    <tr>
                        <td>{{ $row->sale_date }}</td>
                        <td>{{ $row->orders_count }}</td>
                        <td>@money($row->revenue)</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-muted">No sales in this range.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="stat-card">
            <h2 class="h5">Monthly sales</h2>
            <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Month</th><th>Orders</th><th>Revenue</th></tr></thead>
                <tbody>
                @forelse($monthly as $row)
                    <tr>
                        <td>{{ $row->sale_month }}</td>
                        <td>{{ $row->orders_count }}</td>
                        <td>@money($row->revenue)</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-muted">No sales in this range.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="stat-card">
            <h2 class="h5">Order history</h2>
            <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Invoice</th><th>Customer</th><th>Cashier</th><th>Type</th><th>Total</th><th>Date</th><th></th></tr></thead>
                <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td>{{ $order->invoice_number }}</td>
                        <td>{{ $order->customer_name ?: ($order->customer_capture_status?->value === 'customer_declined' ? 'Declined' : '—') }}</td>
                        <td>{{ $order->cashier->name }}</td>
                        <td>{{ $order->type->label() }}</td>
                        <td>@money($order->grand_total)</td>
                        <td>{{ $order->completed_at?->format('Y-m-d H:i') }}</td>
                        <td>
                            <a href="{{ route('orders.receipt', $order) }}" target="_blank">Receipt</a>
                            @if($order->status->value === 'completed')
                                <form class="d-inline" method="POST" action="{{ route('orders.refund', $order) }}" onsubmit="return confirm('Refund this order and reverse inventory?')">
                                    @csrf
                                    <button class="btn btn-link btn-sm p-0">Refund</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection
