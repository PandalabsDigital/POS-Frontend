@extends('layouts.app')

@section('title', 'Inventory items')

@section('content')
@include('inventory._nav')
<div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mb-3">
    <h1 class="h3 mb-0">Items</h1>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('inventory.items.import') }}">Import CSV</a>
        <a class="btn btn-outline-secondary" href="{{ route('inventory.items.export') }}">Export</a>
        <a class="btn btn-accent" href="{{ route('inventory.items.create') }}">Add item</a>
    </div>
</div>
<form class="row g-2 mb-3">
    <div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name, SKU, barcode"></div>
    <div class="col-md-3">
        <select name="category_id" class="form-select">
            <option value="">All categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">All stock</option>
            <option value="in" @selected(request('status')==='in')>In stock</option>
            <option value="low" @selected(request('status')==='low')>Low stock</option>
            <option value="out" @selected(request('status')==='out')>Out of stock</option>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
</form>
<div class="stat-card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Item</th><th>SKU</th><th>Stock</th><th>Min</th><th>Avg cost</th><th>Value</th><th></th></tr></thead>
            <tbody>
            @foreach($items as $item)
                <tr>
                    <td class="fw-semibold">{{ $item->name }}<div class="small text-muted">{{ $item->category?->name }}</div></td>
                    <td>{{ $item->sku ?: '—' }}</td>
                    <td>{{ $item->onHand() }} {{ $item->stockUnit->code }}</td>
                    <td>{{ $item->minimum_stock }}</td>
                    <td>@money($item->average_cost)</td>
                    <td>@money($item->stockValue())</td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-dark" href="{{ route('inventory.items.edit', $item) }}">Edit</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $items->links() }}
</div>
@endsection
