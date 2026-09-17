@extends('layouts.app')

@section('title', $item ? 'Edit item' : 'Add item')

@section('content')
@include('inventory._nav')
<h1 class="h3 mb-3">{{ $item ? 'Edit item' : 'Add item' }}</h1>
<form method="POST" action="{{ $item ? route('inventory.items.update', $item) : route('inventory.items.store') }}" class="stat-card">
    @csrf
    @if($item) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Item name</label><input name="name" class="form-control" value="{{ old('name', $item->name ?? '') }}" required></div>
        <div class="col-md-3"><label class="form-label">SKU</label><input name="sku" class="form-control" value="{{ old('sku', $item->sku ?? '') }}"></div>
        <div class="col-md-3"><label class="form-label">Barcode</label><input name="barcode" class="form-control" value="{{ old('barcode', $item->barcode ?? '') }}"></div>
        <div class="col-md-6">
            <label class="form-label">Category</label>
            <select name="inventory_category_id" class="form-select">
                <option value="">—</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('inventory_category_id', $item->inventory_category_id ?? '')==$category->id)>{{ $category->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6"><label class="form-label">Subcategory</label><input name="subcategory" class="form-control" value="{{ old('subcategory', $item->subcategory ?? '') }}"></div>
        <div class="col-md-4">
            <label class="form-label">Consumption unit</label>
            @php $defaultStock = old('stock_unit_id', $item->stock_unit_id ?? optional($units->firstWhere('code', 'kg'))->id); @endphp
            <select name="stock_unit_id" class="form-select" required>
                @foreach($units as $unit)
                    <option value="{{ $unit->id }}" @selected($defaultStock==$unit->id)>{{ $unit->code }} — {{ $unit->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Purchase unit</label>
            @php $defaultPurchase = old('purchase_unit_id', $item->purchase_unit_id ?? $defaultStock); @endphp
            <select name="purchase_unit_id" class="form-select" required>
                @foreach($units as $unit)
                    <option value="{{ $unit->id }}" @selected($defaultPurchase==$unit->id)>{{ $unit->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4"><label class="form-label">Conversion (purchase → stock)</label><input name="purchase_to_stock_factor" type="number" step="0.000001" min="0.000001" class="form-control" value="{{ old('purchase_to_stock_factor', $item->purchase_to_stock_factor ?? 1) }}"><div class="form-text">e.g. 1 carton = 20 L → 20</div></div>
        <div class="col-md-3"><label class="form-label">Minimum</label><input name="minimum_stock" type="number" step="0.0001" class="form-control" value="{{ old('minimum_stock', $item->minimum_stock ?? 0) }}"></div>
        <div class="col-md-3"><label class="form-label">Reorder level</label><input name="reorder_level" type="number" step="0.0001" class="form-control" value="{{ old('reorder_level', $item->reorder_level ?? '') }}"></div>
        <div class="col-md-3"><label class="form-label">Maximum</label><input name="maximum_stock" type="number" step="0.0001" class="form-control" value="{{ old('maximum_stock', $item->maximum_stock ?? '') }}"></div>
        <div class="col-md-3"><label class="form-label">Reorder qty</label><input name="reorder_quantity" type="number" step="0.0001" class="form-control" value="{{ old('reorder_quantity', $item->reorder_quantity ?? '') }}"></div>
        <div class="col-md-6">
            <label class="form-label">Preferred supplier</label>
            <select name="preferred_supplier_id" class="form-select">
                <option value="">—</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(old('preferred_supplier_id', $item->preferred_supplier_id ?? '')==$supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6"><label class="form-label">Storage location</label><input name="storage_location" class="form-control" value="{{ old('storage_location', $item->storage_location ?? '') }}"></div>
        <div class="col-md-4"><label class="form-label">Tax category</label><input name="tax_category" class="form-control" value="{{ old('tax_category', $item->tax_category ?? '') }}"></div>
        <div class="col-12 d-flex flex-wrap gap-3">
            <label><input type="hidden" name="track_expiry" value="0"><input type="checkbox" name="track_expiry" value="1" @checked(old('track_expiry', $item->track_expiry ?? false))> Expiry tracking</label>
            <label><input type="hidden" name="track_batches" value="0"><input type="checkbox" name="track_batches" value="1" @checked(old('track_batches', $item->track_batches ?? false))> Batch tracking</label>
            <label><input type="hidden" name="is_finished_good" value="0"><input type="checkbox" name="is_finished_good" value="1" @checked(old('is_finished_good', $item->is_finished_good ?? false))> Prepared / finished good</label>
            <label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true))> Active</label>
        </div>
    </div>
    <button class="btn btn-accent mt-4">Save</button>
</form>
@endsection
