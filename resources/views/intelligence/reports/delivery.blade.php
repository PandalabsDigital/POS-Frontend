@extends('layouts.app')

@section('title', $meta['title'].' · Reports')

@section('content')
<div class="print-sheet">
@include('intelligence.reports._page', ['heading' => $meta['title'], 'lede' => $meta['purpose']])
<p class="small text-muted">Aggregator names (Swiggy, Zomato, etc.) are not stored. Channel is derived from order type and payment method.</p>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="stat-card table-responsive">
            <h2 class="h6">Channels</h2>
            <table class="table"><thead><tr><th>Channel</th><th>Orders</th><th>Gross</th><th>Net</th><th>AOV</th></tr></thead><tbody>
            @foreach($channels as $row)
                <tr><td>{{ $row->label }}</td><td>{{ $row->orders }}</td><td>@money($row->gross)</td><td>@money($row->net)</td><td>@money($row->aov)</td></tr>
            @endforeach
            </tbody></table>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="stat-card table-responsive">
            <h2 class="h6">Order types</h2>
            <table class="table"><thead><tr><th>Type</th><th>Orders</th><th>Net</th></tr></thead><tbody>
            @foreach($types as $row)
                <tr><td>{{ $row->label }}</td><td>{{ $row->orders }}</td><td>@money($row->net)</td></tr>
            @endforeach
            </tbody></table>
        </div>
    </div>
</div>
</div>
@endsection
