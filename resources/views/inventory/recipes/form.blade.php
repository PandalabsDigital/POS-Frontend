@extends('layouts.app')
@section('title', $recipe ? 'Edit recipe' : 'Create recipe')
@section('content')
@include('inventory._nav')
<h1 class="h3 mb-3">{{ $recipe ? 'Edit recipe' : 'Create recipe' }}</h1>
@if(!empty($costing) && $recipe?->menuItem)
    <div class="stat-card mb-3">
        <div class="row">
            <div class="col">Selling {{ money($recipe->menuItem->base_price) }}</div>
            <div class="col">Cost {{ money($costing['portion_cost']) }}</div>
            <div class="col">Profit {{ money($recipe->menuItem->base_price - $costing['portion_cost']) }}</div>
            <div class="col">Food cost {{ $recipe->menuItem->base_price > 0 ? round(($costing['portion_cost'] / $recipe->menuItem->base_price) * 100, 1) : 0 }}%</div>
        </div>
        <div class="small text-muted mt-2">Recipe {{ money($costing['recipe_cost']) }} for {{ $costing['yield'] }} portions. Waste {{ money($costing['waste_cost']) }}.</div>
    </div>
@endif
<form method="POST" action="{{ $recipe ? route('inventory.recipes.update', $recipe) : route('inventory.recipes.store') }}" class="stat-card">
@csrf @if($recipe) @method('PUT') @endif
<div class="row g-3 mb-3">
<div class="col-md-4"><label class="form-label">Name</label><input name="name" class="form-control" required value="{{ old('name', $recipe->name ?? '') }}"></div>
<div class="col-md-2">
    <label class="form-label">Type</label>
    <select name="type" class="form-select">
        <option value="sale" @selected(old('type', $recipe->type ?? '')==='sale')>POS dish</option>
        <option value="production" @selected(old('type', $recipe->type ?? '')==='production')>Prep / batch</option>
    </select>
</div>
<div class="col-md-3">
    <label class="form-label">Menu item</label>
    <select name="menu_item_id" class="form-select">
        <option value="">—</option>
        @foreach($menuItems as $menu)
            <option value="{{ $menu->id }}" @selected(old('menu_item_id', $recipe->menu_item_id ?? '')==$menu->id)>{{ $menu->name }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-3">
    <label class="form-label">Finished stock item</label>
    <select name="output_item_id" class="form-select">
        <option value="">—</option>
        @foreach($finishedGoods as $good)
            <option value="{{ $good->id }}" @selected(old('output_item_id', $recipe->output_item_id ?? '')==$good->id)>{{ $good->name }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-3"><label class="form-label">Yield</label><input name="yield_quantity" type="number" step="0.0001" class="form-control" value="{{ old('yield_quantity', $recipe->yield_quantity ?? 1) }}"></div>
<div class="col-md-3">
    <label class="form-label">Yield unit</label>
    <select name="yield_unit_id" class="form-select">
        <option value="">—</option>
        @foreach($units as $unit)<option value="{{ $unit->id }}" @selected(old('yield_unit_id', $recipe->yield_unit_id ?? '')==$unit->id)>{{ $unit->code }}</option>@endforeach
    </select>
</div>
<div class="col-md-3"><label class="form-label">Recipe waste %</label><input name="waste_percent" type="number" step="0.01" class="form-control" value="{{ old('waste_percent', $recipe->waste_percent ?? 0) }}"></div>
<div class="col-12"><label class="form-label">Prep notes</label><textarea name="instructions" class="form-control" rows="2">{{ old('instructions', $recipe->instructions ?? '') }}</textarea></div>
</div>
<h2 class="h6">Ingredients</h2>
<div id="ings">
@php $lines = old('ingredients', $recipe?->ingredients?->toArray() ?? [['inventory_item_id'=>'','quantity'=>'','unit_id'=>'','waste_percent'=>0]]); @endphp
@foreach($lines as $i => $line)
<div class="row g-2 mb-2">
    <div class="col-md-4">
        <select name="ingredients[{{ $i }}][inventory_item_id]" class="form-select" required>
            <option value="">Ingredient</option>
            @foreach($items as $item)
                <option value="{{ $item->id }}" @selected(($line['inventory_item_id'] ?? '')==$item->id)>{{ $item->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><input name="ingredients[{{ $i }}][quantity]" type="number" step="0.0001" class="form-control" placeholder="Qty" value="{{ $line['quantity'] ?? '' }}" required></div>
    <div class="col-md-2">
        <select name="ingredients[{{ $i }}][unit_id]" class="form-select" required>
            @foreach($units as $unit)<option value="{{ $unit->id }}" @selected(($line['unit_id'] ?? '')==$unit->id)>{{ $unit->code }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2"><input name="ingredients[{{ $i }}][waste_percent]" type="number" step="0.01" class="form-control" placeholder="Waste %" value="{{ $line['waste_percent'] ?? 0 }}"></div>
</div>
@endforeach
</div>
<button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="document.getElementById('ings').insertAdjacentHTML('beforeend', document.getElementById('ings').children[0].outerHTML)">Add ingredient</button>
<div><button class="btn btn-accent">Save recipe</button></div>
</form>
@if($recipe && $recipe->type === 'production')
<form method="POST" action="{{ route('inventory.recipes.produce', $recipe) }}" class="stat-card mt-3">
    @csrf
    <h2 class="h6">Produce batch</h2>
    <div class="row g-2">
        <div class="col-md-3"><input name="batches" type="number" step="0.0001" value="1" class="form-control" required></div>
        <div class="col-md-4">
            <select name="inventory_location_id" class="form-select">
                @foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-3"><button class="btn btn-outline-secondary">Produce</button></div>
    </div>
</form>
@endif
@endsection
