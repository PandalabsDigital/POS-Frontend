<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdjustmentController extends Controller
{
    public function index(): View
    {
        return view('inventory.adjustments.index', [
            'items' => InventoryItem::query()->active()->with('stockUnit')->orderBy('name')->get(),
            'locations' => InventoryLocation::query()->active()->orderBy('name')->get(),
            'reasons' => config('inventory.adjustment_reasons'),
        ]);
    }

    public function store(Request $request, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validate([
            'inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'inventory_location_id' => ['required', 'exists:inventory_locations,id'],
            'actual_quantity' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'in:'.implode(',', array_keys(config('inventory.adjustment_reasons')))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $inventory->adjustStock(
            InventoryItem::query()->with('stockUnit')->findOrFail($data['inventory_item_id']),
            (float) $data['actual_quantity'],
            $request->user(),
            InventoryLocation::query()->findOrFail($data['inventory_location_id']),
            $data['reason'],
            $data['notes'] ?? null,
        );

        return back()->with('success', 'Stock adjusted.');
    }
}
