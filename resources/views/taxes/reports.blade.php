@extends('layouts.app')

@section('title', 'Tax reports')

@section('content')
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Tax reports</h1>
        <p class="text-muted mb-0">Collected from invoice snapshots — rate changes never rewrite this history.</p>
    </div>
    <form class="d-flex flex-column flex-sm-row gap-2">
        <input type="date" name="from" class="form-control" value="{{ $from }}">
        <input type="date" name="to" class="form-control" value="{{ $to }}">
        <button class="btn btn-accent">Filter</button>
    </form>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="stat-card">
            <h2 class="h5">Tax summary</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Invoice</th>
                            <th>Order</th>
                            <th>Taxable Sales</th>
                            <th>Tax Name</th>
                            <th>Tax Rate</th>
                            <th>Tax Amount</th>
                            <th>Total Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($summary as $row)
                        <tr>
                            <td>{{ $row['date'] }}</td>
                            <td>{{ $row['invoice'] }}</td>
                            <td>{{ $row['order'] }}</td>
                            <td>@money($row['taxable_sales'])</td>
                            <td>{{ $row['tax_name'] }}</td>
                            <td>{{ $row['tax_rate'] }}%</td>
                            <td>@money($row['tax_amount'])</td>
                            <td>@money($row['total_sales'])</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-muted">No tax invoices in this range.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="stat-card">
            <h2 class="h5">Tax by rate</h2>
            <table class="table">
                <tbody>
                @forelse($byRate as $rate => $amount)
                    <tr><td>{{ $rate }}%</td><td>@money($amount)</td></tr>
                @empty
                    <tr><td class="text-muted">No tax collected.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="stat-card">
            <h2 class="h5">Tax by category</h2>
            <table class="table">
                <tbody>
                @forelse($byCategory as $category => $amount)
                    <tr><td>{{ $category }}</td><td>@money($amount)</td></tr>
                @empty
                    <tr><td class="text-muted">No category tax data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="stat-card">
            <h2 class="h5">Tax collected</h2>
            <h3 class="h6">Daily</h3>
            <table class="table">
                @foreach($collected['daily'] as $day => $amount)
                    <tr><td>{{ $day }}</td><td>@money($amount)</td></tr>
                @endforeach
            </table>
            <h3 class="h6">Weekly</h3>
            <table class="table">
                @foreach($collected['weekly'] as $week => $amount)
                    <tr><td>{{ $week }}</td><td>@money($amount)</td></tr>
                @endforeach
            </table>
            <h3 class="h6">Monthly</h3>
            <table class="table">
                @foreach($collected['monthly'] as $month => $amount)
                    <tr><td>{{ $month }}</td><td>@money($amount)</td></tr>
                @endforeach
            </table>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="stat-card">
            <h2 class="h5">Tax by payment method</h2>
            <table class="table">
                <tbody>
                @forelse($byPayment as $method => $amount)
                    <tr><td>{{ config('taxation.payment_methods.'.$method, ucfirst($method)) }}</td><td>@money($amount)</td></tr>
                @empty
                    <tr><td class="text-muted">No payments in this range.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
