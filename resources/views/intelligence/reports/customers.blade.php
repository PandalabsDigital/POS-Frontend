@extends('layouts.app')

@section('title', $meta['title'].' · Reports')

@section('content')
<div class="print-sheet">
@include('intelligence.reports._page', ['heading' => $meta['title'], 'lede' => $meta['purpose']])
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-label">Orders</div><div class="fs-4">{{ $orders }}</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-label">Unique phones</div><div class="fs-4">{{ $unique }}</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-label">Repeat phones</div><div class="fs-4">{{ $repeat }}</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-label">Declined capture</div><div class="fs-4">{{ $declined }}</div></div></div>
</div>
<div class="stat-card table-responsive">
    <table class="table">
        <thead><tr><th>Name</th><th>Phone</th><th>Visits</th><th>Spent</th></tr></thead>
        <tbody>
        @forelse($top as $row)
            <tr>
                <td>{{ $row->customer_name ?: '—' }}</td>
                <td>{{ $row->customer_phone }}</td>
                <td>{{ $row->visits }}</td>
                <td>@money($row->spent)</td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-muted">No captured customers in this range.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</div>
@endsection
