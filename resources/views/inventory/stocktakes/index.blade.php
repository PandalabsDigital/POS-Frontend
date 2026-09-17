@extends('layouts.app')
@section('title', 'Stocktake')
@section('content')
@include('inventory._nav')
<h1 class="h3 mb-3">Stocktake</h1>
<form method="POST" action="{{ route('inventory.stocktakes.store') }}" class="stat-card mb-4">
    @csrf
    <div class="row g-2">
        <div class="col-md-4"><select name="inventory_location_id" class="form-select">@foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><button class="btn btn-accent">Start count</button></div>
    </div>
</form>
<div class="stat-card table-responsive"><table class="table"><thead><tr><th>#</th><th>Location</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($stocktakes as $row)
<tr><td>{{ $row->id }}</td><td>{{ $row->location->name }}</td><td>{{ $row->status }}</td><td><a href="{{ route('inventory.stocktakes.show', $row) }}">Open</a></td></tr>
@endforeach
</tbody></table>{{ $stocktakes->links() }}</div>
@endsection
