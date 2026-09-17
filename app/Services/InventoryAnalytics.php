<?php

namespace App\Services;

use App\Enums\InventoryTransactionType;
use App\Enums\OrderStatus;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryPurchase;
use App\Models\InventoryTransaction;
use App\Models\InventoryWastage;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class InventoryAnalytics
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        $settings = InventorySettings::current();
        $todayStart = now()->startOfDay();
        $monthStart = now()->startOfMonth();
        $warningDays = $settings['expiry_warning_days'];

        $items = InventoryItem::query()->active()->with(['stockUnit', 'preferredSupplier', 'balances'])->get();
        $low = $items->filter(fn (InventoryItem $item) => $item->isLowStock() && ! $item->isOutOfStock());
        $out = $items->filter(fn (InventoryItem $item) => $item->isOutOfStock());

        $expiring = InventoryBatch::query()
            ->where('quantity', '>', 0)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', now()->addDays($warningDays))
            ->whereDate('expires_at', '>=', now()->toDateString())
            ->count();

        $todayPurchases = (float) InventoryPurchase::query()->whereDate('received_date', now()->toDateString())->sum('total');
        $todayWaste = (float) InventoryWastage::query()->where('occurred_at', '>=', $todayStart)->sum('cost');
        $monthWaste = (float) InventoryWastage::query()->where('occurred_at', '>=', $monthStart)->sum('cost');

        $sales = (float) Order::query()
            ->where('status', OrderStatus::Completed)
            ->where('completed_at', '>=', $monthStart)
            ->sum('grand_total');

        $consumptionCost = (float) InventoryTransaction::query()
            ->where('type', InventoryTransactionType::SaleConsumption)
            ->where('occurred_at', '>=', $monthStart)
            ->sum('total_value');

        $foodCost = $sales > 0 ? round(($consumptionCost / $sales) * 100, 1) : 0.0;
        $stockValue = $this->inventory->calculateInventoryValue();
        $cogs = $consumptionCost + $monthWaste;
        $turnover = $stockValue > 0 ? round($cogs / $stockValue, 2) : 0.0;
        $wastePct = $sales > 0 ? round(($monthWaste / $sales) * 100, 1) : 0.0;

        return [
            'stock_value' => $stockValue,
            'low_stock_count' => $low->count(),
            'out_of_stock_count' => $out->count(),
            'expiring_count' => $expiring,
            'today_purchases' => $todayPurchases,
            'today_wastage' => $todayWaste,
            'month_wastage' => $monthWaste,
            'waste_percent' => $wastePct,
            'food_cost' => $foodCost,
            'food_cost_target' => $settings['food_cost_target'],
            'turnover' => $turnover,
            'low_stock' => $low->take(12)->values(),
        ];
    }

    public function topWasted(Carbon $from, Carbon $to): Collection
    {
        return InventoryWastage::query()
            ->selectRaw('inventory_item_id, SUM(stock_quantity) as qty, SUM(cost) as cost')
            ->whereBetween('occurred_at', [$from, $to])
            ->groupBy('inventory_item_id')
            ->orderByDesc('cost')
            ->with('item')
            ->limit(8)
            ->get();
    }

    public function wastageReasons(Carbon $from, Carbon $to): Collection
    {
        return InventoryWastage::query()
            ->selectRaw('reason, SUM(cost) as cost, COUNT(*) as times')
            ->whereBetween('occurred_at', [$from, $to])
            ->groupBy('reason')
            ->orderByDesc('cost')
            ->get();
    }
}
