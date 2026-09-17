@extends('layouts.app')

@section('title', 'Menu')

@section('content')
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-4">
    <h1 class="h3 mb-0">Menu items</h1>
    <a href="{{ route('menu-items.create') }}" class="btn btn-accent">Add menu item</a>
</div>
<div class="row g-3">
    @foreach($items as $item)
        <div class="col-6 col-md-4 col-xl-3">
            <div class="stat-card h-100">
                @if($item->imageUrl())
                    <img src="{{ $item->imageUrl() }}" class="rounded-3 mb-3 w-100" style="height:140px;object-fit:cover" alt="{{ $item->name }}">
                @endif
                <div class="fw-bold">{{ $item->name }}</div>
                <div class="small text-muted mb-2">{{ $item->category->name }} · @money($item->base_price)</div>
                <div class="small mb-3">{{ $item->sizes->pluck('name')->join(', ') }}</div>
                <a class="btn btn-sm btn-outline-dark" href="{{ route('menu-items.edit', $item) }}">Edit</a>
                <form class="d-inline" method="POST" action="{{ route('menu-items.destroy', $item) }}" onsubmit="return confirm('Delete this item?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
