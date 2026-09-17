@extends('layouts.app')

@section('title', $meta['title'].' · Reports')

@section('content')
<div class="print-sheet">
@include('intelligence.reports._page', ['heading' => $meta['title'], 'lede' => $meta['purpose']])
<p class="small text-muted">Theoretical = POS sale consumption. Actual = consumption + wastage + adjustments. Positive variance means extra stock left the building beyond recipes.</p>
<div class="stat-card table-responsive">
    <table class="table">
        <thead><tr><th>Item</th><th>Theoretical</th><th>Actual</th><th>Variance</th><th>%</th></tr></thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row->item->name }}</td>
                <td>{{ $row->theoretical }}</td>
                <td>{{ $row->actual }}</td>
                <td>{{ $row->variance }}</td>
                <td>{{ $row->percent }}%</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-muted">No stock movement in this range.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</div>
@endsection
