<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\InventoryTransactionType;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryPurchase;
use App\Models\InventoryTransaction;
use App\Models\InventoryWastage;
use App\Models\Order;
use App\Services\InventoryService;
use App\Services\InventorySettings;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports, InventoryService $inventory): View
    {
        $range = $reports->range($request->query('from'), $request->query('to'));
        $from = $range['from'];
        $to = $range['to'];
        $settings = InventorySettings::current();

        $sales = (float) Order::query()->where('status', OrderStatus::Completed)->whereBetween('completed_at', [$from, $to])->sum('grand_total');
        $consumption = (float) InventoryTransaction::query()->where('type', InventoryTransactionType::SaleConsumption)->whereBetween('occurred_at', [$from, $to])->sum('total_value');
        $waste = (float) InventoryWastage::query()->whereBetween('occurred_at', [$from, $to])->sum('cost');
        $purchases = (float) InventoryPurchase::query()->whereBetween('received_date', [$from->toDateString(), $to->toDateString()])->sum('total');
        $foodCost = $sales > 0 ? round(($consumption / $sales) * 100, 1) : 0;

        $theoretical = InventoryTransaction::query()
            ->select('inventory_item_id', DB::raw('SUM(quantity_out) as qty'), DB::raw('SUM(total_value) as cost'))
            ->where('type', InventoryTransactionType::SaleConsumption)
            ->whereBetween('occurred_at', [$from, $to])
            ->groupBy('inventory_item_id')
            ->with('item')
            ->get()
            ->keyBy('inventory_item_id');

        $actual = InventoryTransaction::query()
            ->select('inventory_item_id', DB::raw('SUM(quantity_out) as qty'))
            ->whereIn('type', [
                InventoryTransactionType::SaleConsumption->value,
                InventoryTransactionType::Wastage->value,
                InventoryTransactionType::Adjustment->value,
            ])
            ->whereBetween('occurred_at', [$from, $to])
            ->groupBy('inventory_item_id')
            ->get()
            ->keyBy('inventory_item_id');

        $variance = InventoryItem::query()->with('stockUnit')->orderBy('name')->get()->map(function (InventoryItem $item) use ($theoretical, $actual) {
            $t = (float) ($theoretical[$item->id]->qty ?? 0);
            $a = (float) ($actual[$item->id]->qty ?? 0);

            return (object) [
                'item' => $item,
                'theoretical' => $t,
                'actual' => $a,
                'variance' => round($a - $t, 4),
                'percent' => $t > 0 ? round((($a - $t) / $t) * 100, 1) : ($a > 0 ? 100 : 0),
            ];
        })->filter(fn ($row) => $row->theoretical > 0 || $row->actual > 0)->values();

        return view('inventory.reports.index', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'stock_value' => $inventory->calculateInventoryValue(),
            'sales' => $sales,
            'consumption' => $consumption,
            'waste' => $waste,
            'purchases' => $purchases,
            'food_cost' => $foodCost,
            'target' => $settings['food_cost_target'],
            'summary' => InventoryItem::query()->with(['stockUnit', 'balances'])->orderBy('name')->get(),
            'variance' => $variance,
            'purchaseRows' => InventoryPurchase::query()->with('supplier')->whereBetween('received_date', [$from->toDateString(), $to->toDateString()])->latest('id')->limit(50)->get(),
            'wasteRows' => InventoryWastage::query()->with('item')->whereBetween('occurred_at', [$from, $to])->latest('id')->limit(50)->get(),
        ]);
    }

    public function exportValuation(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Item', 'Qty', 'Unit', 'Avg cost', 'Value']);
            foreach (InventoryItem::query()->with(['stockUnit', 'balances'])->orderBy('name')->get() as $item) {
                fputcsv($out, [$item->name, $item->onHand(), $item->stockUnit->code, $item->average_cost, $item->stockValue()]);
            }
            fclose($out);
        }, 'inventory-valuation.csv');
    }
}
