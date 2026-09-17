@extends('layouts.app')

@section('title', $meta['title'].' · Reports')

@section('content')
<div class="print-sheet">
@include('intelligence.reports._page', ['heading' => $meta['title'], 'lede' => $meta['purpose']])
<div class="stat-card table-responsive">
    <table class="table">
        <thead><tr><th>Invoice</th><th>Supplier</th><th>Received</th><th>Total</th></tr></thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row->invoice_number ?: '—' }}</td>
                <td>{{ $row->supplier?->name ?? '—' }}</td>
                <td>{{ $row->received_date?->toDateString() }}</td>
                <td>@money($row->total)</td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-muted">No purchases received in this range.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</div>
@endsection
