<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\InventoryTransactionType;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryPurchase;
use App\Models\InventorySupplier;
use App\Models\InventoryUnit;
use App\Services\InventoryService;
use App\Services\UnitConverter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $purchases = InventoryPurchase::query()
            ->with(['supplier', 'location', 'user'])
            ->when($request->filled('supplier_id'), fn ($query) => $query->where('inventory_supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('received_date', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('received_date', '<=', $request->query('to')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.purchases.index', [
            'purchases' => $purchases,
            'suppliers' => InventorySupplier::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('inventory.purchases.create', [
            'suppliers' => InventorySupplier::query()->active()->orderBy('name')->get(),
            'locations' => InventoryLocation::query()->active()->orderBy('name')->get(),
            'items' => InventoryItem::query()->active()->with(['stockUnit', 'purchaseUnit'])->orderBy('name')->get(),
            'units' => InventoryUnit::query()->orderBy('code')->get(),
            'defaultLocation' => InventoryLocation::defaultLocation(),
        ]);
    }

    public function store(Request $request, InventoryService $inventory, UnitConverter $converter): RedirectResponse
    {
        $data = $request->validate([
            'inventory_supplier_id' => ['nullable', 'exists:inventory_suppliers,id'],
            'inventory_location_id' => ['required', 'exists:inventory_locations,id'],
            'invoice_number' => ['nullable', 'string', 'max:80'],
            'invoice_date' => ['nullable', 'date'],
            'received_date' => ['required', 'date'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'lines.*.unit_id' => ['required', 'exists:inventory_units,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.batch_number' => ['nullable', 'string', 'max:80'],
            'lines.*.expires_at' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($data, $inventory, $converter, $request) {
            $location = InventoryLocation::query()->findOrFail($data['inventory_location_id']);
            $purchase = InventoryPurchase::query()->create([
                'inventory_supplier_id' => $data['inventory_supplier_id'] ?? null,
                'inventory_location_id' => $location->id,
                'invoice_number' => $data['invoice_number'] ?? null,
                'invoice_date' => $data['invoice_date'] ?? null,
                'received_date' => $data['received_date'],
                'discount_amount' => $data['discount_amount'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'user_id' => $request->user()->id,
                'status' => 'received',
            ]);

            $subtotal = 0;
            $tax = 0;

            foreach ($data['lines'] as $line) {
                $item = InventoryItem::query()->with(['stockUnit', 'purchaseUnit'])->findOrFail($line['inventory_item_id']);
                $unit = InventoryUnit::query()->findOrFail($line['unit_id']);
                $qty = (float) $line['quantity'];
                $unitCost = (float) $line['unit_cost'];
                $lineTax = (float) ($line['tax_amount'] ?? 0);
                $lineDiscount = (float) ($line['discount_amount'] ?? 0);
                $lineTotal = ($qty * $unitCost) + $lineTax - $lineDiscount;
                $stockQty = $converter->toStockQuantity($item, $qty, $unit);

                $purchase->items()->create([
                    'inventory_item_id' => $item->id,
                    'unit_id' => $unit->id,
                    'quantity' => $qty,
                    'stock_quantity' => $stockQty,
                    'unit_cost' => $unitCost,
                    'tax_amount' => $lineTax,
                    'discount_amount' => $lineDiscount,
                    'line_total' => $lineTotal,
                    'batch_number' => $line['batch_number'] ?? null,
                    'expires_at' => $line['expires_at'] ?? null,
                ]);

                $inventory->receiveStock(
                    $item,
                    $qty,
                    $unit,
                    $unitCost,
                    $request->user(),
                    $location,
                    InventoryTransactionType::Purchase,
                    'purchase',
                    [],
                    $line['batch_number'] ?? null,
                    $line['expires_at'] ?? null,
                    'purchase',
                    $purchase->id,
                    $purchase->invoice_number,
                );

                if ($purchase->inventory_supplier_id) {
                    $item->suppliers()->syncWithoutDetaching([
                        $purchase->inventory_supplier_id => ['last_cost' => $unitCost],
                    ]);
                    if (! $item->preferred_supplier_id) {
                        $item->update(['preferred_supplier_id' => $purchase->inventory_supplier_id]);
                    }
                }

                $subtotal += $qty * $unitCost;
                $tax += $lineTax;
            }

            $purchase->update([
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'total' => $subtotal + $tax - (float) $purchase->discount_amount,
            ]);
        });

        return redirect()->route('inventory.purchases.index')->with('success', 'Stock received.');
    }

    public function show(InventoryPurchase $purchase): View
    {
        $purchase->load(['supplier', 'location', 'user', 'items.item', 'items.unit']);

        return view('inventory.purchases.show', compact('purchase'));
    }
}
