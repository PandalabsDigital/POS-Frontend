@extends('layouts.app')

@section('title', 'Customer Capture')

@section('content')
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Customer Capture</h1>
        <p class="text-muted mb-0">Identified guests vs declined, anonymous, and staff skip reasons. Low rates are for coaching, not automatic punishment.</p>
    </div>
    <form class="d-flex flex-column flex-sm-row gap-2">
        <input type="date" name="from" class="form-control" value="{{ $from }}">
        <input type="date" name="to" class="form-control" value="{{ $to }}">
        <button class="btn btn-accent">Filter</button>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        <div class="stat-card">
            <div class="stat-label">Total orders</div>
            <div class="display-6">{{ $summary['total_orders'] }}</div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="stat-card">
            <div class="stat-label">Identified customers</div>
            <div class="display-6">{{ $summary['identified'] }}</div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="stat-card">
            <div class="stat-label">Customer declined</div>
            <div class="display-6">{{ $summary['declined'] }}</div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="stat-card">
            <div class="stat-label">Anonymous orders</div>
            <div class="display-6">{{ $summary['anonymous'] }}</div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="stat-card">
            <div class="stat-label">Staff unable</div>
            <div class="display-6">{{ $summary['staff_unable'] }}</div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="stat-card">
            <div class="stat-label">Capture rate</div>
            <div class="display-6">{{ $summary['capture_rate'] }}%</div>
            <div class="small text-muted">Identified / total orders</div>
        </div>
    </div>
</div>

<div class="stat-card">
    <h2 class="h5 mb-3">Staff performance</h2>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Staff</th>
                    <th>Orders</th>
                    <th>Customers captured</th>
                    <th>Declined</th>
                    <th>Staff unable</th>
                    <th>Anonymous</th>
                    <th>Capture rate</th>
                </tr>
            </thead>
            <tbody>
            @forelse($staff as $row)
                <tr>
                    <td class="fw-semibold">{{ $row->staff }}</td>
                    <td>{{ $row->orders }}</td>
                    <td>{{ $row->captured }}</td>
                    <td>{{ $row->declined }}</td>
                    <td>{{ $row->unable }}</td>
                    <td>{{ $row->anonymous }}</td>
                    <td>{{ number_format($row->rate, 1) }}%</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-muted">No completed orders in this range.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
