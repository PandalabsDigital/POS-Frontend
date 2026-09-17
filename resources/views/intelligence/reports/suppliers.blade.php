@extends('layouts.app')

@section('title', $meta['title'].' · Reports')

@section('content')
<div class="print-sheet">
@include('intelligence.reports._page', ['heading' => $meta['title'], 'lede' => $meta['purpose']])
<div class="stat-card table-responsive">
    <table class="table">
        <thead><tr><th>Supplier</th><th>Purchases</th><th>Value</th></tr></thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row->name }}</td>
                <td>{{ $row->period_count }}</td>
                <td>@money($row->period_total ?? 0)</td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-muted">No suppliers.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</div>
@endsection
