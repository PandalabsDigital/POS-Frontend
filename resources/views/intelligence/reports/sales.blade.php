@extends('layouts.app')

@section('title', $meta['title'].' · Reports')

@section('content')
<div class="print-sheet">
@include('intelligence.reports._page', ['heading' => $meta['title']])
@php $c = $compared['current']; @endphp
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="stat-card intel-mini"><i class="bi bi-cash-stack" aria-hidden="true"></i><strong>@money($c['gross'])</strong><span>Gross</span></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card intel-mini"><i class="bi bi-wallet2" aria-hidden="true"></i><strong>@money($c['net'])</strong><span>Net</span>@include('intelligence.reports._delta', ['delta' => $compared['delta']['net']])</div></div>
    <div class="col-6 col-lg-3"><div class="stat-card intel-mini"><i class="bi bi-receipt" aria-hidden="true"></i><strong>{{ $c['orders'] }}</strong><span>Orders</span></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card intel-mini"><i class="bi bi-receipt-cutoff" aria-hidden="true"></i><strong>@money($c['aov'])</strong><span>Avg ticket</span></div></div>
</div>
<div class="stat-card mb-4">
    <h2 class="h6"><i class="bi bi-graph-up" aria-hidden="true"></i> Sales by day</h2>
    <canvas id="revChart" height="90"></canvas>
</div>
<div class="row g-3 mb-4">
    @foreach($types as $row)
        <div class="col-4">
            <div class="stat-card intel-mini">
                <i class="bi {{ $row->key === 'delivery' ? 'bi-bicycle' : ($row->key === 'takeaway' ? 'bi-bag' : 'bi-shop') }}" aria-hidden="true"></i>
                <strong>@money($row->net)</strong>
                <span>{{ $row->label }} · {{ $row->orders }}</span>
            </div>
        </div>
    @endforeach
</div>
<div class="stat-card table-responsive mb-4">
    <table class="table mb-0">
        <thead><tr><th>Day</th><th>Net</th><th>Orders</th><th>Avg</th></tr></thead>
        <tbody>
        @forelse($daily as $row)
            <tr><td>{{ $row->bucket }}</td><td>@money($row->net)</td><td>{{ $row->orders }}</td><td>@money($row->aov)</td></tr>
        @empty
            <tr><td colspan="4" class="text-muted">No sales in this range.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<details class="stat-card">
    <summary class="intel-summary"><i class="bi bi-list-ul" aria-hidden="true"></i> Tickets</summary>
    <div class="table-responsive mt-3">
        <table class="table mb-0">
            <thead><tr><th>Invoice</th><th>Type</th><th>Total</th></tr></thead>
            <tbody>
            @foreach($history as $order)
                <tr>
                    <td><a href="{{ route('orders.receipt', $order) }}">{{ $order->invoice_number }}</a></td>
                    <td>{{ $order->type->label() }}</td>
                    <td>@money($order->grand_total)</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $history->links() }}
    </div>
</details>
</div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('revChart'), {
    type: 'line',
    data: { labels: @json($daily->pluck('bucket')), datasets: [{ data: @json($daily->pluck('net')), borderColor: '#ff6a00', fill: true, backgroundColor: 'rgba(255,106,0,.16)', tension: .35 }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>
@endpush
