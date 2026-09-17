<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryTransaction;
use App\Services\InventorySettings;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        $items = InventoryItem::query()
            ->with(['stockUnit', 'category', 'preferredSupplier', 'balances.location'])
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('location_id'), function ($query) use ($request) {
                $query->whereHas('balances', fn ($builder) => $builder->where('inventory_location_id', $request->integer('location_id')));
            })
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('inventory.stock.index', [
            'items' => $items,
            'locations' => InventoryLocation::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function ledger(Request $request): View
    {
        $txns = InventoryTransaction::query()
            ->with(['item.stockUnit', 'location', 'user'])
            ->when($request->filled('item_id'), fn ($query) => $query->where('inventory_item_id', $request->integer('item_id')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->query('type')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('occurred_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('occurred_at', '<=', $request->query('to')))
            ->latest('id')
            ->paginate(40)
            ->withQueryString();

        return view('inventory.stock.ledger', [
            'txns' => $txns,
            'items' => InventoryItem::query()->orderBy('name')->get(),
        ]);
    }

    public function low(): View
    {
        $items = InventoryItem::query()->active()->with(['stockUnit', 'preferredSupplier', 'balances'])->orderBy('name')->get()
            ->filter(fn (InventoryItem $item) => $item->isLowStock());

        return view('inventory.stock.low', compact('items'));
    }

    public function expiry(): View
    {
        $days = InventorySettings::current()['expiry_warning_days'];
        $batches = InventoryBatch::query()
            ->with(['item.stockUnit', 'location'])
            ->where('quantity', '>', 0)
            ->whereNotNull('expires_at')
            ->orderBy('expires_at')
            ->get();

        return view('inventory.stock.expiry', compact('batches', 'days'));
    }

    public function exportLedger(Request $request): StreamedResponse
    {
        return response()->streamDownload(function () use ($request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Item', 'Type', 'In', 'Out', 'Balance', 'Cost', 'Value', 'User', 'Reference']);
            InventoryTransaction::query()
                ->with(['item', 'user'])
                ->when($request->filled('from'), fn ($query) => $query->whereDate('occurred_at', '>=', $request->query('from')))
                ->when($request->filled('to'), fn ($query) => $query->whereDate('occurred_at', '<=', $request->query('to')))
                ->orderBy('id')
                ->chunk(200, function ($chunk) use ($out) {
                    foreach ($chunk as $txn) {
                        fputcsv($out, [
                            $txn->occurred_at?->format('Y-m-d H:i'),
                            $txn->item->name,
                            $txn->type->label(),
                            $txn->quantity_in,
                            $txn->quantity_out,
                            $txn->balance_after,
                            $txn->unit_cost,
                            $txn->total_value,
                            $txn->user?->name,
                            $txn->reference_number,
                        ]);
                    }
                });
            fclose($out);
        }, 'stock-ledger.csv');
    }
}
