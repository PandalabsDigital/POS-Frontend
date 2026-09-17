@extends('layouts.app')

@section('title', $meta['title'].' · Reports')

@section('content')
<div class="print-sheet">
@include('intelligence.reports._page', ['heading' => $meta['title'], 'lede' => $meta['purpose']])
<div class="stat-card table-responsive">
    <table class="table">
        <thead><tr><th>Employee</th><th>Orders</th><th>Gross</th><th>Discount %</th><th>Net</th><th>Tax</th><th>AOV</th></tr></thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row->name }}</td>
                <td>{{ $row->orders }}</td>
                <td>@money($row->gross)</td>
                <td>{{ $row->avg_discount }}%</td>
                <td>@money($row->net)</td>
                <td>@money($row->tax)</td>
                <td>@money($row->aov)</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-muted">No employee sales in this range.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</div>
@endsection
