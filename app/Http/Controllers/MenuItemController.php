<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use App\Models\Category;
use App\Models\MenuItem;
use App\Services\RecipeCosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MenuItemController extends Controller
{
    public function index(): View
    {
        return view('menu.index', [
            'items' => MenuItem::query()->with(['category', 'sizes'])->latest()->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('menu.create', [
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreMenuItemRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $path = $request->file('image')?->store('menu', 'public');

            $item = MenuItem::query()->create([
                'category_id' => $request->validated('category_id'),
                'name' => $request->validated('name'),
                'description' => $request->validated('description'),
                'base_price' => $request->validated('base_price'),
                'image_path' => $path,
                'is_active' => $request->boolean('is_active', true),
                'tax_rule_id' => $request->validated('tax_rule_id') ?: null,
            ]);

            $this->syncSizes($item, $request->validated('sizes') ?? []);
        });

        return redirect()->route('menu-items.index')->with('success', 'Menu item added.');
    }

    public function edit(MenuItem $menuItem): View
    {
        $menuItem->load(['sizes', 'inventoryRecipe.ingredients.item.stockUnit', 'inventoryRecipe.ingredients.unit']);

        $costing = null;
        if ($menuItem->inventoryRecipe) {
            $costing = app(RecipeCosting::class)->summarize($menuItem->inventoryRecipe);
        }

        return view('menu.edit', [
            'item' => $menuItem,
            'categories' => Category::query()->orderBy('name')->get(),
            'recipeCosting' => $costing,
        ]);
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem): RedirectResponse
    {
        DB::transaction(function () use ($request, $menuItem) {
            $data = [
                'category_id' => $request->validated('category_id'),
                'name' => $request->validated('name'),
                'description' => $request->validated('description'),
                'base_price' => $request->validated('base_price'),
                'is_active' => $request->boolean('is_active', true),
                'tax_rule_id' => $request->validated('tax_rule_id') ?: null,
            ];

            if ($request->hasFile('image')) {
                if ($menuItem->image_path) {
                    Storage::disk('public')->delete($menuItem->image_path);
                }
                $data['image_path'] = $request->file('image')->store('menu', 'public');
            }

            $menuItem->update($data);
            $this->syncSizes($menuItem, $request->validated('sizes') ?? []);
        });

        return redirect()->route('menu-items.index')->with('success', 'Menu item updated.');
    }

    public function destroy(MenuItem $menuItem): RedirectResponse
    {
        if ($menuItem->image_path) {
            Storage::disk('public')->delete($menuItem->image_path);
        }

        $menuItem->delete();

        return redirect()->route('menu-items.index')->with('success', 'Menu item deleted.');
    }

    /**
     * @param  array<int, array{name: string, price: mixed}>  $sizes
     */
    private function syncSizes(MenuItem $item, array $sizes): void
    {
        $item->sizes()->delete();

        foreach (array_values($sizes) as $index => $size) {
            $item->sizes()->create([
                'name' => $size['name'],
                'price' => $size['price'],
                'sort_order' => $index,
                'is_active' => filter_var($size['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
