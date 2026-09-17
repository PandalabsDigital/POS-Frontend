@extends('layouts.app')

@section('title', $meta['title'].' · Reports')

@section('content')
<div class="print-sheet">
@include('intelligence.reports._page', ['heading' => $meta['title'], 'lede' => $meta['purpose']])
<div class="stat-card mb-3"><div class="stat-label">Wastage cost</div><div class="fs-4">@money($total)</div></div>
<div class="stat-card table-responsive">
    <table class="table">
        <thead><tr><th>When</th><th>Item</th><th>Qty</th><th>Reason</th><th>Cost</th><th>By</th></tr></thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row->occurred_at?->format('Y-m-d H:i') }}</td>
                <td>{{ $row->item?->name }}</td>
                <td>{{ $row->quantity }}</td>
                <td>{{ $row->reason }}</td>
                <td>@money($row->cost)</td>
                <td>{{ $row->user?->name }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-muted">No wastage recorded.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</div>
@endsection
