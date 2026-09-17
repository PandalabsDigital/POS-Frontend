@extends('layouts.app')

@section('title', $meta['title'].' · Reports')

@section('content')
<div class="print-sheet">
@include('intelligence.reports._page', ['heading' => $meta['title'], 'lede' => $meta['purpose']])
<p class="small text-muted"><a href="{{ route('taxes.reports', ['from' => $filter->from->toDateString(), 'to' => $filter->to->toDateString()]) }}">Open the full tax workspace</a> for audit export. Figures below are completed tickets only.</p>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="stat-card table-responsive">
            <h2 class="h6">By rate</h2>
            <table class="table"><thead><tr><th>Rate</th><th>Tax</th></tr></thead><tbody>
            @forelse($byRate as $rate => $amount)
                <tr><td>{{ $rate }}%</td><td>@money($amount)</td></tr>
            @empty
                <tr><td colspan="2" class="text-muted">No tax collected.</td></tr>
            @endforelse
            </tbody></table>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="stat-card table-responsive">
            <h2 class="h6">By category</h2>
            <table class="table"><thead><tr><th>Category</th><th>Tax</th></tr></thead><tbody>
            @forelse($byCategory as $category => $amount)
                <tr><td>{{ $category }}</td><td>@money($amount)</td></tr>
            @empty
                <tr><td colspan="2" class="text-muted">No line tax.</td></tr>
            @endforelse
            </tbody></table>
        </div>
    </div>
</div>
<div class="stat-card table-responsive mt-4">
    <h2 class="h6">Invoices</h2>
    <table class="table"><thead><tr><th>Date</th><th>Invoice</th><th>Tax name</th><th>Rate</th><th>Tax</th><th>Total</th></tr></thead><tbody>
    @forelse($summary as $row)
        <tr>
            <td>{{ $row['date'] }}</td>
            <td>{{ $row['invoice'] }}</td>
            <td>{{ $row['tax_name'] }}</td>
            <td>{{ $row['tax_rate'] }}%</td>
            <td>@money($row['tax_amount'])</td>
            <td>@money($row['total_sales'])</td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted">No tax rows.</td></tr>
    @endforelse
    </tbody></table>
</div>
</div>
@endsection
