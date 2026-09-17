@extends('layouts.app')
@section('title', 'Locations')
@section('content')
@include('inventory._nav')
<h1 class="h3 mb-3">Locations</h1>
<form method="POST" action="{{ route('inventory.locations.store') }}" class="row g-2 mb-4">
    @csrf
    <div class="col-md-4"><input name="name" class="form-control" placeholder="Name" required></div>
    <div class="col-md-3"><input name="code" class="form-control" placeholder="Code"></div>
    <div class="col-md-3"><label class="mt-2"><input type="checkbox" name="is_default" value="1"> Default</label></div>
    <div class="col-md-2"><button class="btn btn-accent">Add</button></div>
</form>
<div class="stat-card table-responsive"><table class="table"><thead><tr><th>Name</th><th>Default</th></tr></thead><tbody>
@foreach($locations as $location)<tr><td>{{ $location->name }}</td><td>{{ $location->is_default ? 'Yes' : '' }}</td></tr>@endforeach
</tbody></table></div>
<p><a href="{{ route('inventory.expiry') }}">Expiry alerts</a></p>
@endsection
