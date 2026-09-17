<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryStocktake;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StocktakeController extends Controller
{
    public function index(): View
    {
        $stocktakes = InventoryStocktake::query()->with(['location', 'user'])->latest('id')->paginate(15);

        return view('inventory.stocktakes.index', [
            'stocktakes' => $stocktakes,
            'locations' => InventoryLocation::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'inventory_location_id' => ['required', 'exists:inventory_locations,id'],
        ]);

        $location = InventoryLocation::query()->findOrFail($data['inventory_location_id']);
        $stocktake = InventoryStocktake::query()->create([
            'inventory_location_id' => $location->id,
            'status' => 'draft',
            'user_id' => $request->user()->id,
        ]);

        $items = InventoryItem::query()->active()->with(['balances' => fn ($query) => $query->where('inventory_location_id', $location->id)])->orderBy('name')->get();
        foreach ($items as $item) {
            $expected = (float) ($item->balances->first()?->quantity ?? 0);
            $stocktake->items()->create([
                'inventory_item_id' => $item->id,
                'expected_quantity' => $expected,
            ]);
        }

        return redirect()->route('inventory.stocktakes.show', $stocktake);
    }

    public function show(InventoryStocktake $stocktake): View
    {
        $stocktake->load(['location', 'items.item.stockUnit']);

        return view('inventory.stocktakes.show', compact('stocktake'));
    }

    public function update(Request $request, InventoryStocktake $stocktake): RedirectResponse
    {
        abort_if($stocktake->status !== 'draft', 403);

        $data = $request->validate([
            'counts' => ['required', 'array'],
            'counts.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach ($stocktake->items as $line) {
            if (! array_key_exists($line->id, $data['counts'])) {
                continue;
            }
            $counted = $data['counts'][$line->id];
            $line->update([
                'counted_quantity' => $counted,
                'difference' => $counted === null || $counted === '' ? null : round((float) $counted - (float) $line->expected_quantity, 4),
            ]);
        }

        return back()->with('success', 'Counts saved. Review then confirm.');
    }

    public function confirm(Request $request, InventoryStocktake $stocktake, InventoryService $inventory): RedirectResponse
    {
        abort_if($stocktake->status !== 'draft', 403);

        DB::transaction(function () use ($stocktake, $inventory, $request) {
            $stocktake->load(['items.item.stockUnit', 'location']);
            foreach ($stocktake->items as $line) {
                if ($line->counted_quantity === null) {
                    continue;
                }
                $inventory->adjustStock(
                    $line->item,
                    (float) $line->counted_quantity,
                    $request->user(),
                    $stocktake->location,
                    'physical_count',
                    'Stocktake #'.$stocktake->id,
                );
            }
            $stocktake->update(['status' => 'confirmed', 'confirmed_at' => now()]);
        });

        return redirect()->route('inventory.stocktakes.index')->with('success', 'Stocktake confirmed. Adjustments posted.');
    }
}
