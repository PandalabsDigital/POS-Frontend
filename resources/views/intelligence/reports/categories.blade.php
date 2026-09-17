@extends('layouts.app')

@section('title', $meta['title'].' · Reports')

@section('content')
<div class="print-sheet">
@include('intelligence.reports._page', ['heading' => $meta['title'], 'lede' => $meta['purpose']])
@forelse($rows as $category)
<div class="stat-card mb-4">
    <div class="d-flex justify-content-between flex-wrap gap-2">
        <h2 class="h5 mb-0">{{ $category->name }}</h2>
        @if($category->category_id)
            <a class="small" href="{{ route('reports.show', array_merge($filter->query(), ['report' => 'products', 'category_id' => $category->category_id])) }}">Products in this category</a>
        @endif
    </div>
    <div class="row g-3 mt-1">
        <div class="col-6 col-md"><div class="stat-label">Revenue</div>@money($category->revenue)</div>
        <div class="col-6 col-md"><div class="stat-label">Units</div>{{ $category->units }}</div>
        <div class="col-6 col-md"><div class="stat-label">% of net</div>{{ $category->share }}%</div>
        <div class="col-6 col-md"><div class="stat-label">Food cost</div>@money($category->food_cost)</div>
        <div class="col-6 col-md"><div class="stat-label">Gross profit</div>@money($category->gross_profit)</div>
        <div class="col-6 col-md"><div class="stat-label">Margin</div>{{ $category->margin === null ? '—' : $category->margin.'%' }}</div>
    </div>
    <div class="table-responsive mt-3">
        <table class="table mb-0">
            <thead><tr><th>Product</th><th>Units</th><th>Net</th><th></th></tr></thead>
            <tbody>
            @foreach($category->products as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->units }}</td>
                    <td>@money($product->net)</td>
                    <td><a href="{{ route('reports.show', array_merge($filter->query(), ['report' => 'sales', 'product_id' => $product->menu_item_id])) }}">Orders</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@empty
<div class="stat-card text-muted">No category sales in this range.</div>
@endforelse
</div>
@endsection
