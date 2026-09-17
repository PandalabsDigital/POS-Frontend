@extends('layouts.app')

@section('title', $meta['title'].' · Reports')

@section('content')
<div class="print-sheet">
@include('intelligence.reports._page', ['heading' => $meta['title'], 'lede' => $meta['purpose']])
<p class="small text-muted">Expected payment equals recorded POS tenders. Card/UPI settlement files from banks or aggregators are not imported, so settlement stays blank instead of a fake match.</p>
<div class="stat-card table-responsive">
    <table class="table">
        <thead><tr><th>Method</th><th>Transactions</th><th>Amount</th><th>Refunds</th><th>Net</th><th>Expected</th><th>Recorded</th><th>Settlement</th></tr></thead>
        <tbody>
        @foreach($payments as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td>{{ $row['transactions'] }}</td>
                <td>@money($row['amount'])</td>
                <td>@money($row['refunds'])</td>
                <td>@money($row['net'])</td>
                <td>@money($row['expected'])</td>
                <td>@money($row['recorded'])</td>
                <td class="text-muted">Not recorded</td>
            </tr>
        @endforeach
        <tr>
            <td>Bank transfer</td>
            <td colspan="7" class="text-muted">Not a tender on this POS.</td>
        </tr>
        </tbody>
    </table>
</div>
</div>
@endsection
