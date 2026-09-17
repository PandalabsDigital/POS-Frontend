@extends('layouts.app')
@section('title', 'Suppliers')
@section('content')
@include('inventory._nav')
<div class="d-flex justify-content-between mb-3"><h1 class="h3 mb-0">Suppliers</h1><a class="btn btn-accent" href="{{ route('inventory.suppliers.create') }}">Add supplier</a></div>
<div class="stat-card table-responsive"><table class="table"><thead><tr><th>Name</th><th>Phone</th><th>Purchases</th><th></th></tr></thead><tbody>
@foreach($suppliers as $supplier)
<tr><td>{{ $supplier->name }}</td><td>{{ $supplier->phone ?: '—' }}</td><td>{{ $supplier->purchases_count }}</td><td><a href="{{ route('inventory.suppliers.edit', $supplier) }}">Edit</a></td></tr>
@endforeach
</tbody></table>{{ $suppliers->links() }}</div>
@endsection
