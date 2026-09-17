@extends('layouts.app')

@section('title', 'Edit menu item')

@section('content')
<h1 class="h3 mb-4">Edit menu item</h1>
@if(!empty($recipeCosting))
    <div class="stat-card mb-3">
        <div class="fw-semibold mb-1">Recipe cost</div>
        <div>Selling @money($item->base_price) · Cost @money($recipeCosting['portion_cost']) · Profit @money($item->base_price - $recipeCosting['portion_cost']) · Food cost {{ $item->base_price > 0 ? round(($recipeCosting['portion_cost'] / $item->base_price) * 100, 1) : 0 }}%</div>
        <a href="{{ route('inventory.recipes.edit', $item->inventoryRecipe) }}">Edit inventory recipe</a>
    </div>
@else
    <p class="text-muted"><a href="{{ route('inventory.recipes.create') }}">Add an inventory recipe</a> to track food cost for this dish.</p>
@endif
<form method="POST" action="{{ route('menu-items.update', $item) }}" enctype="multipart/form-data" class="stat-card">
    @csrf @method('PUT')
    @include('menu._form', ['item' => $item])
    <button class="btn btn-accent">Update</button>
</form>
@endsection
