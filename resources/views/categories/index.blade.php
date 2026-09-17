@extends('layouts.app')

@section('title', 'Categories')

@section('content')
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-4">
    <h1 class="h3 mb-0">Categories</h1>
    <a href="{{ route('categories.create') }}" class="btn btn-accent">Add category</a>
</div>
<div class="d-none d-md-block">
    <div class="stat-card">
        <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Name</th><th>Items</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach($categories as $category)
                <tr>
                    <td class="fw-semibold">{{ $category->name }}</td>
                    <td>{{ $category->menu_items_count }}</td>
                    <td>
                        <button class="btn btn-sm {{ $category->is_active ? 'btn-success' : 'btn-outline-secondary' }}" data-toggle-category="{{ route('categories.toggle', $category) }}">
                            {{ $category->is_active ? 'Enabled' : 'Disabled' }}
                        </button>
                    </td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-dark" href="{{ route('categories.edit', $category) }}">Edit</a>
                        <form class="d-inline" method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
</div>
<div class="d-md-none d-grid gap-3">
    @foreach($categories as $category)
        <div class="category-mobile-card">
            <div class="fw-semibold mb-1">{{ $category->name }}</div>
            <div class="small text-muted mb-3">{{ $category->menu_items_count }} items</div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-sm {{ $category->is_active ? 'btn-success' : 'btn-outline-secondary' }}" data-toggle-category="{{ route('categories.toggle', $category) }}">
                    {{ $category->is_active ? 'Enabled' : 'Disabled' }}
                </button>
                <a class="btn btn-sm btn-outline-dark" href="{{ route('categories.edit', $category) }}">Edit</a>
                <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endsection
