<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryUnit;
use App\Models\InventoryWastage;
use App\Services\InventoryAnalytics;
use App\Services\InventoryService;
use App\Services\ReportService;
use App\Services\UnitConverter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WastageController extends Controller
{
    public function index(Request $request, ReportService $reports, InventoryAnalytics $analytics): View
    {
        $range = $reports->range($request->query('from'), $request->query('to'));
        $rows = InventoryWastage::query()
            ->with(['item.stockUnit', 'location', 'user', 'unit'])
            ->whereBetween('occurred_at', [$range['from'], $range['to']])
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('inventory.wastage.index', [
            'rows' => $rows,
            'from' => $range['from']->toDateString(),
            'to' => $range['to']->toDateString(),
            'items' => InventoryItem::query()->active()->with('stockUnit')->orderBy('name')->get(),
            'locations' => InventoryLocation::query()->active()->orderBy('name')->get(),
            'units' => InventoryUnit::query()->orderBy('code')->get(),
            'reasons' => config('inventory.wastage_reasons'),
            'top' => $analytics->topWasted($range['from'], $range['to']),
            'reasonStats' => $analytics->wastageReasons($range['from'], $range['to']),
            'monthCost' => (float) InventoryWastage::query()->where('occurred_at', '>=', now()->startOfMonth())->sum('cost'),
        ]);
    }

    public function store(Request $request, InventoryService $inventory, UnitConverter $converter): RedirectResponse
    {
        $data = $request->validate([
            'inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'inventory_location_id' => ['required', 'exists:inventory_locations,id'],
            'unit_id' => ['required', 'exists:inventory_units,id'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'reason' => ['required', 'in:'.implode(',', array_keys(config('inventory.wastage_reasons')))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($data, $request, $inventory, $converter) {
            $item = InventoryItem::query()->with('stockUnit')->findOrFail($data['inventory_item_id']);
            $unit = InventoryUnit::query()->findOrFail($data['unit_id']);
            $location = InventoryLocation::query()->findOrFail($data['inventory_location_id']);
            $stockQty = $converter->toStockQuantity($item, (float) $data['quantity'], $unit);
            $cost = $stockQty * (float) $item->average_cost;

            $wastage = InventoryWastage::query()->create([
                'inventory_item_id' => $item->id,
                'inventory_location_id' => $location->id,
                'unit_id' => $unit->id,
                'quantity' => $data['quantity'],
                'stock_quantity' => $stockQty,
                'cost' => $cost,
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'user_id' => $request->user()->id,
                'occurred_at' => now(),
            ]);

            $inventory->recordWastage(
                $item,
                (float) $data['quantity'],
                $unit,
                $request->user(),
                $location,
                $data['reason'],
                $data['notes'] ?? null,
            );

            $wastage->update(['cost' => $stockQty * (float) $item->fresh()->average_cost]);
        });

        return back()->with('success', 'Wastage recorded.');
    }
}
