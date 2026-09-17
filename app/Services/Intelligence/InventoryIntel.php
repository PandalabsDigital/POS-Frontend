<?php

namespace App\Services\Intelligence;

use App\Enums\InventoryTransactionType;
use App\Enums\OrderStatus;
use App\Models\InventoryItem;
use App\Models\InventoryPurchase;
use App\Models\InventorySupplier;
use App\Models\InventoryTransaction;
use App\Models\InventoryWastage;
use App\Models\Order;
use App\Services\InventoryService;
use App\Services\InventorySettings;
use Illuminate\Support\Facades\DB;

class InventoryIntel
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(ReportFilter $filter): array
    {
        $from = $filter->from;
        $to = $filter->to;
        $settings = InventorySettings::current();
        $sales = (float) Order::query()
            ->where('status', OrderStatus::Completed)
            ->whereBetween('completed_at', [$from, $to])
            ->sum('grand_total');
        $consumption = (float) InventoryTransaction::query()
            ->where('type', InventoryTransactionType::SaleConsumption)
            ->whereBetween('occurred_at', [$from, $to])
            ->sum('total_value');
        $waste = (float) InventoryWastage::query()->whereBetween('occurred_at', [$from, $to])->sum('cost');
        $purchases = (float) InventoryPurchase::query()
            ->whereBetween('received_date', [$from->toDateString(), $to->toDateString()])
            ->sum('total');

        return [
            'stock_value' => $this->inventory->calculateInventoryValue(),
            'sales' => $sales,
            'consumption' => $consumption,
            'waste' => $waste,
            'purchases' => $purchases,
            'food_cost' => $sales > 0 ? round(($consumption / $sales) * 100, 1) : 0.0,
            'target' => $settings['food_cost_target'],
        ];
    }

    public function wastage(ReportFilter $filter)
    {
        return InventoryWastage::query()
            ->with(['item', 'user'])
            ->whereBetween('occurred_at', [$filter->from, $filter->to])
            ->latest('occurred_at')
            ->get();
    }

    public function purchases(ReportFilter $filter)
    {
        return InventoryPurchase::query()
            ->with('supplier')
            ->whereBetween('received_date', [$filter->from->toDateString(), $filter->to->toDateString()])
            ->latest('id')
            ->get();
    }

    public function suppliers(ReportFilter $filter)
    {
        $from = $filter->from->toDateString();
        $to = $filter->to->toDateString();

        return InventorySupplier::query()
            ->withSum(['purchases as period_total' => fn ($q) => $q->whereBetween('received_date', [$from, $to])], 'total')
            ->withCount(['purchases as period_count' => fn ($q) => $q->whereBetween('received_date', [$from, $to])])
            ->orderBy('name')
            ->get();
    }

    public function variance(ReportFilter $filter)
    {
        $from = $filter->from;
        $to = $filter->to;

        $theoretical = InventoryTransaction::query()
            ->select('inventory_item_id', DB::raw('SUM(quantity_out) as qty'))
            ->where('type', InventoryTransactionType::SaleConsumption)
            ->whereBetween('occurred_at', [$from, $to])
            ->groupBy('inventory_item_id')
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

        return InventoryItem::query()->with('stockUnit')->orderBy('name')->get()->map(function (InventoryItem $item) use ($theoretical, $actual) {
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
    }
}
