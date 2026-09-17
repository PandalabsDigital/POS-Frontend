@extends('layouts.app')
@section('title', 'Inventory categories')
@section('content')
@include('inventory._nav')
<h1 class="h3 mb-3">Categories</h1>
<form method="POST" action="{{ route('inventory.categories.store') }}" class="row g-2 mb-4">
    @csrf
    <div class="col-md-3"><input name="group_name" class="form-control" placeholder="Group (Food, Packaging…)" required></div>
    <div class="col-md-5"><input name="name" class="form-control" placeholder="Category name" required></div>
    <div class="col-md-2"><button class="btn btn-accent w-100">Add</button></div>
</form>
@foreach($categories as $group => $rows)
    <div class="stat-card mb-3">
        <h2 class="h5">{{ $group }}</h2>
        <div class="table-responsive"><table class="table mb-0"><tbody>
        @foreach($rows as $category)
            <tr>
                <td>{{ $category->name }}</td>
                <td>{{ $category->items_count }} items</td>
                <td class="text-end">
                    <form method="POST" action="{{ route('inventory.categories.destroy', $category) }}" class="d-inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                </td>
            </tr>
        @endforeach
        </tbody></table></div>
    </div>
@endforeach
@endsection
