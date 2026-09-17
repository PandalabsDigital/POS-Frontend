@extends('layouts.app')
@section('title', $supplier ? 'Edit supplier' : 'Add supplier')
@section('content')
@include('inventory._nav')
<h1 class="h3 mb-3">{{ $supplier ? 'Edit supplier' : 'Add supplier' }}</h1>
<form method="POST" action="{{ $supplier ? route('inventory.suppliers.update', $supplier) : route('inventory.suppliers.store') }}" class="stat-card">
@csrf @if($supplier) @method('PUT') @endif
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" required value="{{ old('name', $supplier->name ?? '') }}"></div>
<div class="col-md-6"><label class="form-label">Contact</label><input name="contact_name" class="form-control" value="{{ old('contact_name', $supplier->contact_name ?? '') }}"></div>
<div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone', $supplier->phone ?? '') }}"></div>
<div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $supplier->email ?? '') }}"></div>
<div class="col-12"><label class="form-label">Address</label><input name="address" class="form-control" value="{{ old('address', $supplier->address ?? '') }}"></div>
<div class="col-md-4"><label class="form-label">Tax/VAT no.</label><input name="tax_number" class="form-control" value="{{ old('tax_number', $supplier->tax_number ?? '') }}"></div>
<div class="col-md-4"><label class="form-label">Payment terms</label><input name="payment_terms" class="form-control" value="{{ old('payment_terms', $supplier->payment_terms ?? '') }}"></div>
<div class="col-md-4"><label class="form-label">Currency</label><input name="currency_code" class="form-control" maxlength="3" value="{{ old('currency_code', $supplier->currency_code ?? '') }}"></div>
<div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2">{{ old('notes', $supplier->notes ?? '') }}</textarea></div>
<div class="col-12"><label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier->is_active ?? true))> Active</label></div>
</div>
<button class="btn btn-accent mt-3">Save</button>
</form>
@endsection
