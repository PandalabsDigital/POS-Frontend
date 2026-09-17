<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryRecipe;
use App\Models\InventoryUnit;
use App\Models\MenuItem;
use App\Services\InventoryService;
use App\Services\RecipeCosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecipeController extends Controller
{
    public function index(): View
    {
        $recipes = InventoryRecipe::query()->with(['menuItem', 'outputItem', 'ingredients'])->orderBy('name')->paginate(20);

        return view('inventory.recipes.index', compact('recipes'));
    }

    public function create(): View
    {
        return view('inventory.recipes.form', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $recipe = InventoryRecipe::query()->create($this->validated($request));
        $this->syncIngredients($recipe, $request);

        return redirect()->route('inventory.recipes.edit', $recipe)->with('success', 'Recipe saved.');
    }

    public function edit(InventoryRecipe $recipe, RecipeCosting $costing): View
    {
        $recipe->load(['ingredients.item', 'ingredients.unit', 'menuItem']);

        return view('inventory.recipes.form', $this->formData($recipe) + [
            'costing' => $costing->summarize($recipe),
        ]);
    }

    public function update(Request $request, InventoryRecipe $recipe): RedirectResponse
    {
        $recipe->update($this->validated($request) + ['version' => $recipe->version + 1]);
        $this->syncIngredients($recipe, $request);

        return redirect()->route('inventory.recipes.edit', $recipe)->with('success', 'Recipe updated. Future sales will use this version.');
    }

    public function destroy(InventoryRecipe $recipe): RedirectResponse
    {
        $recipe->delete();

        return redirect()->route('inventory.recipes.index')->with('success', 'Recipe deleted.');
    }

    public function produce(Request $request, InventoryRecipe $recipe, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validate([
            'batches' => ['required', 'numeric', 'min:0.0001'],
            'inventory_location_id' => ['required', 'exists:inventory_locations,id'],
        ]);

        $inventory->produce(
            $recipe,
            (float) $data['batches'],
            $request->user(),
            InventoryLocation::query()->findOrFail($data['inventory_location_id']),
        );

        return back()->with('success', 'Production recorded.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?InventoryRecipe $recipe = null): array
    {
        return [
            'recipe' => $recipe,
            'menuItems' => MenuItem::query()->orderBy('name')->get(),
            'items' => InventoryItem::query()->active()->with('stockUnit')->orderBy('name')->get(),
            'finishedGoods' => InventoryItem::query()->where('is_finished_good', true)->orderBy('name')->get(),
            'units' => InventoryUnit::query()->orderBy('code')->get(),
            'locations' => InventoryLocation::query()->active()->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:sale,production'],
            'menu_item_id' => ['nullable', 'exists:menu_items,id'],
            'output_item_id' => ['nullable', 'exists:inventory_items,id'],
            'yield_quantity' => ['required', 'numeric', 'min:0.0001'],
            'yield_unit_id' => ['nullable', 'exists:inventory_units,id'],
            'waste_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['waste_percent'] = $data['waste_percent'] ?? 0;

        return $data;
    }

    private function syncIngredients(InventoryRecipe $recipe, Request $request): void
    {
        $lines = $request->validate([
            'ingredients' => ['nullable', 'array'],
            'ingredients.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'ingredients.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'ingredients.*.unit_id' => ['required', 'exists:inventory_units,id'],
            'ingredients.*.waste_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ingredients.*.is_essential' => ['sometimes', 'boolean'],
        ])['ingredients'] ?? [];

        $recipe->ingredients()->delete();
        foreach (array_values($lines) as $index => $line) {
            $recipe->ingredients()->create([
                'inventory_item_id' => $line['inventory_item_id'],
                'quantity' => $line['quantity'],
                'unit_id' => $line['unit_id'],
                'waste_percent' => $line['waste_percent'] ?? 0,
                'is_essential' => (bool) ($line['is_essential'] ?? true),
                'sort_order' => $index,
            ]);
        }
    }
}
