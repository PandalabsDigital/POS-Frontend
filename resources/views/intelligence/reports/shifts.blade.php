@extends('layouts.app')

@section('title', $meta['title'].' · Reports')

@section('content')
<div class="print-sheet">
@include('intelligence.reports._page', ['heading' => $meta['title'], 'lede' => $meta['purpose']])
<p class="small text-muted">Clock-in / clock-out is not recorded. Each row is a cashier’s first sale to last sale on a calendar day.</p>
<div class="stat-card table-responsive">
    <table class="table">
        <thead><tr><th>Date</th><th>Employee</th><th>First sale</th><th>Last sale</th><th>Orders</th><th>Net</th><th>Discount %</th></tr></thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row->date }}</td>
                <td>{{ $row->employee }}</td>
                <td>{{ $row->first_sale }}</td>
                <td>{{ $row->last_sale }}</td>
                <td>{{ $row->orders }}</td>
                <td>@money($row->net)</td>
                <td>{{ $row->discount_percent }}%</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-muted">No cashier-day activity.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</div>
@endsection
