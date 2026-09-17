@extends('layouts.app')
@section('title', 'Units')
@section('content')
@include('inventory._nav')
<h1 class="h3 mb-3">Units</h1>
<form method="POST" action="{{ route('inventory.units.store') }}" class="row g-2 mb-4">
    @csrf
    <div class="col-md-2"><input name="code" class="form-control" placeholder="Code" required></div>
    <div class="col-md-3"><input name="name" class="form-control" placeholder="Name" required></div>
    <div class="col-md-2"><select name="dimension" class="form-select"><option value="mass">Weight</option><option value="volume">Volume</option><option value="count">Count</option><option value="other">Other</option></select></div>
    <div class="col-md-2"><input name="to_base" type="number" step="any" class="form-control" value="1" required></div>
    <div class="col-md-2"><button class="btn btn-accent">Add custom unit</button></div>
</form>
<div class="stat-card table-responsive"><table class="table"><thead><tr><th>Code</th><th>Name</th><th>Type</th></tr></thead><tbody>
@foreach($units as $unit)<tr><td>{{ $unit->code }}</td><td>{{ $unit->name }}</td><td>{{ $unit->dimension }}</td></tr>@endforeach
</tbody></table></div>
@endsection
