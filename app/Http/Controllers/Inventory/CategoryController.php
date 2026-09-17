<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('inventory.categories.index', [
            'categories' => InventoryCategory::query()->withCount('items')->orderBy('group_name')->orderBy('sort_order')->get()->groupBy('group_name'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'group_name' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:80'],
        ]);

        InventoryCategory::query()->create($data + ['is_active' => true, 'sort_order' => 999]);

        return back()->with('success', 'Category added.');
    }

    public function update(Request $request, InventoryCategory $category): RedirectResponse
    {
        $category->update($request->validate([
            'group_name' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:80'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', true)]);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(InventoryCategory $category): RedirectResponse
    {
        $category->delete();

        return back()->with('success', 'Category deleted.');
    }
}
