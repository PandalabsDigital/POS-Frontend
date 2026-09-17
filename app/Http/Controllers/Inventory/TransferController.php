<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryTransfer;
use App\Models\InventoryUnit;
use App\Services\InventoryService;
use App\Services\UnitConverter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function index(): View
    {
        $transfers = InventoryTransfer::query()->with(['fromLocation', 'toLocation', 'user', 'items'])->latest('id')->paginate(20);

        return view('inventory.transfers.index', [
            'transfers' => $transfers,
            'locations' => InventoryLocation::query()->active()->orderBy('name')->get(),
            'items' => InventoryItem::query()->active()->with('stockUnit')->orderBy('name')->get(),
            'units' => InventoryUnit::query()->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request, InventoryService $inventory, UnitConverter $converter): RedirectResponse
    {
        $data = $request->validate([
            'from_location_id' => ['required', 'exists:inventory_locations,id'],
            'to_location_id' => ['required', 'exists:inventory_locations,id', 'different:from_location_id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'lines.*.unit_id' => ['required', 'exists:inventory_units,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
        ]);

        DB::transaction(function () use ($data, $request, $inventory, $converter) {
            $from = InventoryLocation::query()->findOrFail($data['from_location_id']);
            $to = InventoryLocation::query()->findOrFail($data['to_location_id']);
            $transfer = InventoryTransfer::query()->create([
                'from_location_id' => $from->id,
                'to_location_id' => $to->id,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
                'user_id' => $request->user()->id,
                'completed_at' => now(),
            ]);

            $payload = [];
            foreach ($data['lines'] as $line) {
                $item = InventoryItem::query()->with(['stockUnit', 'purchaseUnit'])->findOrFail($line['inventory_item_id']);
                $unit = InventoryUnit::query()->findOrFail($line['unit_id']);
                $stockQty = $converter->toStockQuantity($item, (float) $line['quantity'], $unit);
                $transfer->items()->create([
                    'inventory_item_id' => $item->id,
                    'unit_id' => $unit->id,
                    'quantity' => $line['quantity'],
                    'stock_quantity' => $stockQty,
                ]);
                $payload[] = ['item' => $item, 'quantity' => (float) $line['quantity'], 'unit' => $unit];
            }

            $inventory->transferStock($from, $to, $payload, $request->user(), $data['notes'] ?? null, $transfer->id);
        });

        return back()->with('success', 'Transfer completed.');
    }
}
