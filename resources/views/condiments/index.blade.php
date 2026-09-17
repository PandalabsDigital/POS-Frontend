@extends('layouts.app')

@section('title', 'Add Ons')

@section('content')
<h1 class="h3 mb-4">Add Ons</h1>
<div class="row g-4">
    <div class="col-lg-4">
        <form method="POST" action="{{ route('condiments.store') }}" class="stat-card">
            @csrf
            <h2 class="h5">Add add-on</h2>
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Price</label>
                <input type="number" step="0.01" min="0" name="price" class="form-control" required>
            </div>
            <label class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                <span class="form-check-label">Active</span>
            </label>
            <button class="btn btn-accent">Add</button>
        </form>
    </div>
    <div class="col-lg-8">
        <div class="stat-card">
            @forelse($condiments as $condiment)
                <div class="d-flex flex-wrap gap-2 align-items-start mb-3 pb-3 border-bottom">
                    <form method="POST" action="{{ route('condiments.update', $condiment) }}" class="row g-2 flex-grow-1">
                        @csrf @method('PUT')
                        <div class="col-md-5">
                            <input name="name" class="form-control" value="{{ $condiment->name }}">
                        </div>
                        <div class="col-md-3">
                            <input type="number" step="0.01" name="price" class="form-control" value="{{ $condiment->price }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-center">
                            <label class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($condiment->is_active)>
                                <span class="form-check-label">On</span>
                            </label>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-sm btn-outline-dark w-100">Save</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('condiments.destroy', $condiment) }}" onsubmit="return confirm('Delete this add-on?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </div>
            @empty
                <p class="text-muted mb-0">No add-ons yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
