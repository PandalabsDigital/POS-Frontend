@extends('layouts.app')
@section('title', 'Receive stock')
@section('content')
@include('inventory._nav')
<h1 class="h3 mb-3">Receive stock</h1>
<form method="POST" action="{{ route('inventory.purchases.store') }}" class="stat-card" id="purchase-form">
    @csrf
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label">Supplier</label>
            <select name="inventory_supplier_id" class="form-select">
                <option value="">—</option>
                @foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Location</label>
            <select name="inventory_location_id" class="form-select" required>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}" @selected($defaultLocation->id===$location->id)>{{ $location->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4"><label class="form-label">Invoice #</label><input name="invoice_number" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Invoice date</label><input type="date" name="invoice_date" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Received date</label><input type="date" name="received_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
    </div>
    <h2 class="h6">Lines</h2>
    <div id="lines">
        <div class="row g-2 mb-2 line">
            <div class="col-md-4">
                <select name="lines[0][inventory_item_id]" class="form-select" required>
                    <option value="">Item</option>
                    @foreach($items as $item)<option value="{{ $item->id }}" data-unit="{{ $item->purchase_unit_id }}">{{ $item->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2"><input name="lines[0][quantity]" type="number" step="0.0001" min="0.0001" class="form-control" placeholder="Qty" required></div>
            <div class="col-md-2">
                <select name="lines[0][unit_id]" class="form-select" required>
                    @foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->code }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2"><input name="lines[0][unit_cost]" type="number" step="0.01" min="0" class="form-control" placeholder="Cost" required></div>
            <div class="col-md-2"><input name="lines[0][batch_number]" class="form-control" placeholder="Batch"></div>
            <div class="col-md-2"><input type="date" name="lines[0][expires_at]" class="form-control"></div>
        </div>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="addLine()">Add line</button>
    <div><button type="submit" class="btn btn-accent">Confirm receipt</button></div>
</form>
<script>
function addLine() {
    const wrap = document.getElementById('lines');
    const i = wrap.children.length;
    wrap.insertAdjacentHTML('beforeend', wrap.children[0].outerHTML.replaceAll('lines[0]', 'lines['+i+']'));
}
document.getElementById('lines').addEventListener('change', function (e) {
    if (!e.target.name || !e.target.name.includes('[inventory_item_id]')) return;
    const unit = e.target.selectedOptions[0]?.dataset.unit;
    if (unit) {
        e.target.closest('.line').querySelector('[name$="[unit_id]"]').value = unit;
    }
});
</script>
@endsection
