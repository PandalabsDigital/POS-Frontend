<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventorySupplier;
use App\Models\InventoryUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $items = InventoryItem::query()
            ->with(['category', 'stockUnit', 'preferredSupplier', 'balances'])
            ->when($request->filled('q'), fn ($query) => $query->where(function ($builder) use ($request) {
                $q = $request->string('q')->toString();
                $builder->where('name', 'like', '%'.$q.'%')
                    ->orWhere('sku', 'like', '%'.$q.'%')
                    ->orWhere('barcode', 'like', '%'.$q.'%');
            }))
            ->when($request->filled('category_id'), fn ($query) => $query->where('inventory_category_id', $request->integer('category_id')))
            ->when($request->filled('supplier_id'), fn ($query) => $query->where('preferred_supplier_id', $request->integer('supplier_id')))
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        if ($status) {
            $items->setCollection($items->getCollection()->filter(function (InventoryItem $item) use ($status) {
                return match ($status) {
                    'low' => $item->isLowStock() && ! $item->isOutOfStock(),
                    'out' => $item->isOutOfStock(),
                    'in' => ! $item->isOutOfStock(),
                    default => true,
                };
            })->values());
        }

        return view('inventory.items.index', [
            'items' => $items,
            'categories' => InventoryCategory::query()->active()->orderBy('group_name')->orderBy('name')->get(),
            'suppliers' => InventorySupplier::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('inventory.items.form', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $item = InventoryItem::query()->create($this->validated($request));
        $this->syncSuppliers($item, $request);

        return redirect()->route('inventory.items.index')->with('success', 'Item added.');
    }

    public function edit(InventoryItem $item): View
    {
        return view('inventory.items.form', $this->formData($item));
    }

    public function update(Request $request, InventoryItem $item): RedirectResponse
    {
        $item->update($this->validated($request, $item->id));
        $this->syncSuppliers($item, $request);

        return redirect()->route('inventory.items.index')->with('success', 'Item updated.');
    }

    public function destroy(InventoryItem $item): RedirectResponse
    {
        $item->delete();

        return redirect()->route('inventory.items.index')->with('success', 'Item deleted.');
    }

    public function export(): StreamedResponse
    {
        $filename = 'inventory-items-'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'SKU', 'Category', 'Unit', 'On hand', 'Min', 'Average cost', 'Value', 'Supplier']);
            InventoryItem::query()->with(['category', 'stockUnit', 'preferredSupplier', 'balances'])->orderBy('name')->chunk(100, function ($chunk) use ($out) {
                foreach ($chunk as $item) {
                    fputcsv($out, [
                        $item->name,
                        $item->sku,
                        $item->category?->label(),
                        $item->stockUnit->code,
                        $item->onHand(),
                        $item->minimum_stock,
                        $item->average_cost,
                        $item->stockValue(),
                        $item->preferredSupplier?->name,
                    ]);
                }
            });
            fclose($out);
        }, $filename);
    }

    public function importForm(): View
    {
        return view('inventory.items.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt']]);
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle) ?: [];
        $created = 0;
        $kg = InventoryUnit::query()->where('code', 'kg')->first() ?? InventoryUnit::query()->first();

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine(array_map(fn ($h) => strtolower(trim((string) $h)), $header), $row);
            if (! $data || blank($data['item name'] ?? $data['name'] ?? null)) {
                continue;
            }
            $unit = InventoryUnit::query()->where('code', $data['unit'] ?? $data['purchase unit'] ?? 'kg')->first() ?? $kg;
            $purchase = InventoryUnit::query()->where('code', $data['purchase unit'] ?? $unit->code)->first() ?? $unit;
            $category = null;
            if (filled($data['category'] ?? null)) {
                $category = InventoryCategory::query()->where('name', $data['category'])->first();
            }
            $supplier = null;
            if (filled($data['supplier'] ?? null)) {
                $supplier = InventorySupplier::query()->firstOrCreate(['name' => $data['supplier']]);
            }

            $name = $data['item name'] ?? $data['name'];
            $sku = filled($data['sku'] ?? null) ? trim((string) $data['sku']) : null;
            InventoryItem::query()->updateOrCreate(
                $sku ? ['sku' => $sku] : ['name' => $name],
                [
                    'name' => $name,
                    'sku' => $sku,
                    'inventory_category_id' => $category?->id,
                    'stock_unit_id' => $unit->id,
                    'purchase_unit_id' => $purchase->id,
                    'purchase_to_stock_factor' => (float) ($data['conversion'] ?? 1) ?: 1,
                    'minimum_stock' => (float) ($data['minimum stock'] ?? 0),
                    'maximum_stock' => $data['maximum stock'] ?? null,
                    'reorder_level' => $data['reorder level'] ?? null,
                    'reorder_quantity' => $data['reorder quantity'] ?? null,
                    'average_cost' => (float) ($data['average cost'] ?? 0),
                    'preferred_supplier_id' => $supplier?->id,
                    'is_active' => true,
                ],
            );
            $created++;
        }
        fclose($handle);

        return redirect()->route('inventory.items.index')->with('success', "Imported {$created} rows.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?InventoryItem $item = null): array
    {
        return [
            'item' => $item,
            'categories' => InventoryCategory::query()->active()->orderBy('group_name')->orderBy('name')->get(),
            'units' => InventoryUnit::query()->orderBy('dimension')->orderBy('code')->get(),
            'suppliers' => InventorySupplier::query()->active()->orderBy('name')->get(),
            'locations' => InventoryLocation::query()->active()->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['nullable', 'string', 'max:64', Rule::unique('inventory_items', 'sku')->ignore($ignoreId)],
            'barcode' => ['nullable', 'string', 'max:64'],
            'inventory_category_id' => ['nullable', 'exists:inventory_categories,id'],
            'subcategory' => ['nullable', 'string', 'max:80'],
            'stock_unit_id' => ['required', 'exists:inventory_units,id'],
            'purchase_unit_id' => ['required', 'exists:inventory_units,id'],
            'purchase_to_stock_factor' => ['required', 'numeric', 'min:0.000001'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'maximum_stock' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0'],
            'preferred_supplier_id' => ['nullable', 'exists:inventory_suppliers,id'],
            'storage_location' => ['nullable', 'string', 'max:80'],
            'tax_category' => ['nullable', 'string', 'max:80'],
            'track_expiry' => ['sometimes', 'boolean'],
            'track_batches' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'is_finished_good' => ['sometimes', 'boolean'],
        ]);

        $data['sku'] = filled($data['sku'] ?? null) ? $data['sku'] : null;
        $data['barcode'] = filled($data['barcode'] ?? null) ? $data['barcode'] : null;
        $data['track_expiry'] = $request->boolean('track_expiry');
        $data['track_batches'] = $request->boolean('track_batches');
        $data['is_active'] = $request->boolean('is_active');
        $data['is_finished_good'] = $request->boolean('is_finished_good');

        return $data;
    }

    private function syncSuppliers(InventoryItem $item, Request $request): void
    {
        $ids = array_filter((array) $request->input('supplier_ids', []));
        if ($request->filled('preferred_supplier_id')) {
            $ids[] = $request->integer('preferred_supplier_id');
        }
        $item->suppliers()->sync(array_unique($ids));
    }
}
