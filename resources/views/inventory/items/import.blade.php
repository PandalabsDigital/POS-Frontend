@extends('layouts.app')
@section('title', 'Import items')
@section('content')
@include('inventory._nav')
<h1 class="h3 mb-3">Import items</h1>
<form method="POST" action="{{ route('inventory.items.import.store') }}" enctype="multipart/form-data" class="stat-card">
    @csrf
    <p class="text-muted">CSV columns: Item Name, SKU, Category, Unit, Purchase Unit, Conversion, Minimum Stock, Maximum Stock, Reorder Level, Reorder Quantity, Average Cost, Supplier</p>
    <input type="file" name="file" class="form-control mb-3" accept=".csv,text/csv" required>
    <button class="btn btn-accent">Import</button>
</form>
@endsection
