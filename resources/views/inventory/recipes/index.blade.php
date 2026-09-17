@extends('layouts.app')
@section('title', 'Inventory recipes')
@section('content')
@include('inventory._nav')
<div class="d-flex justify-content-between mb-3"><h1 class="h3 mb-0">Recipes / BOM</h1><a class="btn btn-accent" href="{{ route('inventory.recipes.create') }}">Create recipe</a></div>
<p class="text-muted">POS YouTube recipes stay on the Recipes page. These recipes deduct stock when a dish is sold.</p>
<div class="stat-card table-responsive"><table class="table"><thead><tr><th>Name</th><th>Type</th><th>Menu item</th><th>Ingredients</th><th></th></tr></thead><tbody>
@foreach($recipes as $recipe)
<tr>
    <td>{{ $recipe->name }}</td>
    <td>{{ $recipe->type }}</td>
    <td>{{ $recipe->menuItem?->name ?? '—' }}</td>
    <td>{{ $recipe->ingredients->count() }}</td>
    <td><a href="{{ route('inventory.recipes.edit', $recipe) }}">Edit</a></td>
</tr>
@endforeach
</tbody></table>{{ $recipes->links() }}</div>
@endsection
