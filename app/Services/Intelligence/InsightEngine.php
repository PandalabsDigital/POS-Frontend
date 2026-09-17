<?php

namespace App\Services\Intelligence;

use App\Enums\InventoryTransactionType;
use App\Enums\OrderStatus;
use App\Models\InventoryPurchase;
use App\Models\InventoryTransaction;
use App\Models\InventoryWastage;
use App\Models\Order;
use App\Services\InventorySettings;

class InsightEngine
{
    public function __construct(
        private SalesMetrics $sales,
        private ProductAnalytics $products,
    ) {}

    /**
     * @param  array<string, mixed>  $compared
     * @return list<array{severity: string, title: string, body: string, action: string}>
     */
    public function build(ReportFilter $filter, array $compared): array
    {
        $current = $compared['current'];
        $previous = $compared['previous'];
        $insights = [];

        if ($current['orders'] === 0) {
            return [[
                'severity' => 'info',
                'title' => 'No completed sales in this range',
                'body' => 'Insights appear after orders are taken on the POS. Filters still apply to inventory, purchases, and wastage when those records exist.',
                'action' => 'Open the POS and complete an order, or widen the date range.',
            ]];
        }

        $netDelta = $compared['delta']['net']['percent'];
        if ($netDelta !== null) {
            $direction = $netDelta >= 0 ? 'up' : 'down';
            $insights[] = [
                'severity' => $netDelta >= 0 ? 'positive' : 'warning',
                'title' => 'Net sales are '.$direction.' '.$this->pct($netDelta).' vs the comparison period',
                'body' => 'Net sales were '.$this->money($current['net']).' versus '.$this->money($previous['net']).' in the comparison window.',
                'action' => $netDelta < 0
                    ? 'Check hourly sales and discounts for the drop. Review voids/refunds and staff coverage on weak days.'
                    : 'Protect the gain: keep best sellers in stock and avoid extra discounting during strong hours.',
            ];
        }

        if ($current['discounts'] > 0 && $current['gross'] > 0) {
            $rate = round(($current['discounts'] / $current['gross']) * 100, 1);
            $insights[] = [
                'severity' => $rate > 8 ? 'warning' : 'info',
                'title' => 'Discounts are '.$rate.'% of gross sales',
                'body' => $this->money($current['discounts']).' was discounted across '.$current['orders'].' completed orders.',
                'action' => $rate > 8
                    ? 'Tighten manager approval on large discounts and review the Discount report by employee.'
                    : 'Discounting is modest. Watch employee outliers rather than cutting promotions blindly.',
            ];
        }

        $employees = $this->sales->byEmployee($filter);
        if ($employees->count() > 1) {
            $avgDiscount = $employees->avg('avg_discount');
            $outlier = $employees->sortByDesc('avg_discount')->first();
            if ($outlier && $avgDiscount > 0 && $outlier->avg_discount >= $avgDiscount * 2 && $outlier->discounts > 0) {
                $multiple = round($outlier->avg_discount / $avgDiscount, 1);
                $insights[] = [
                    'severity' => 'alert',
                    'title' => $outlier->name.' applied '.$multiple.'× the average discount %',
                    'body' => $outlier->name.' discounted '.$outlier->avg_discount.'% of sales versus '.$this->pct($avgDiscount).' across cashiers.',
                    'action' => 'Open the Discount report, filter to this employee, and confirm each discounted ticket is authorized.',
                ];
            }
        }

        $refundCount = Order::query()
            ->where('status', OrderStatus::Refunded)
            ->whereBetween('completed_at', [$filter->from, $filter->to])
            ->count();
        if ($current['orders'] > 0 && $refundCount > 0) {
            $rate = round(($refundCount / max($current['orders'] + $refundCount, 1)) * 100, 1);
            $insights[] = [
                'severity' => $rate >= 5 ? 'warning' : 'info',
                'title' => 'Refund rate is '.$rate.'% of settled tickets',
                'body' => $refundCount.' refunded orders totaling '.$this->money($current['refunds']).'. This POS does not record item voids separately.',
                'action' => 'Review the Voids & Refunds report for cashier patterns and repeat products.',
            ];
        }

        $menu = $this->products->products($filter);
        $stars = $menu->where('classification', 'star');
        $dogs = $menu->where('classification', 'dog');
        $puzzles = $menu->where('classification', 'puzzle');
        $plow = $menu->where('classification', 'plow_horse');

        if ($stars->isNotEmpty()) {
            $top = $stars->sortByDesc('gross_profit')->first();
            $insights[] = [
                'severity' => 'positive',
                'title' => $top->name.' is a star (high sales and high margin)',
                'body' => $top->units.' sold, net '.$this->money($top->net).($top->margin !== null ? ', margin '.$top->margin.'%' : '').'.',
                'action' => 'Keep it available, train staff to suggest it, and avoid discounting it.',
            ];
        }
        if ($plow->isNotEmpty()) {
            $item = $plow->sortBy('margin')->first();
            $insights[] = [
                'severity' => 'warning',
                'title' => $item->name.' is a plow horse (popular, weaker margin)',
                'body' => 'High volume with '.($item->margin !== null ? $item->margin.'% margin' : 'unknown margin').'.',
                'action' => 'Review portion size, recipe waste, and add-on attachments before raising the price.',
            ];
        }
        if ($puzzles->isNotEmpty()) {
            $item = $puzzles->sortByDesc('margin')->first();
            $insights[] = [
                'severity' => 'info',
                'title' => $item->name.' is a puzzle (strong margin, low sales)',
                'body' => 'Guests who buy it are profitable, but volume is below the median.',
                'action' => 'Feature it on the POS, pair it with a star, or train cashiers to recommend it.',
            ];
        }
        if ($dogs->isNotEmpty()) {
            $item = $dogs->sortBy('units')->first();
            $insights[] = [
                'severity' => 'alert',
                'title' => $item->name.' is a dog (low sales and low margin)',
                'body' => $item->units.' sold in this range.',
                'action' => 'Consider removing it, replacing the recipe, or stopping prep to cut waste.',
            ];
        }

        $waste = (float) InventoryWastage::query()->whereBetween('occurred_at', [$filter->from, $filter->to])->sum('cost');
        if ($waste > 0) {
            $share = $current['net'] > 0 ? round(($waste / $current['net']) * 100, 1) : null;
            $insights[] = [
                'severity' => ($share !== null && $share >= 3) ? 'warning' : 'info',
                'title' => 'Wastage cost '.$this->money($waste).($share !== null ? ' ('.$share.'% of net sales)' : ''),
                'body' => 'Recorded in inventory wastage, not estimated.',
                'action' => 'Open the Wastage report and cut prep on dog items and over-produced recipes.',
            ];
        }

        $target = InventorySettings::current()['food_cost_target'];
        $consumption = (float) InventoryTransaction::query()
            ->where('type', InventoryTransactionType::SaleConsumption)
            ->whereBetween('occurred_at', [$filter->from, $filter->to])
            ->sum('total_value');
        if ($current['net'] > 0 && $consumption > 0) {
            $foodCost = round(($consumption / $current['net']) * 100, 1);
            $insights[] = [
                'severity' => $foodCost > $target ? 'warning' : 'positive',
                'title' => 'Food cost is '.$foodCost.'% vs a '.$target.'% target',
                'body' => 'Ingredient consumption from POS sales is '.$this->money($consumption).' against net sales '.$this->money($current['net']).'.',
                'action' => $foodCost > $target
                    ? 'Check stock variance, wastage, and plow-horse recipes.'
                    : 'Food cost is on or under target. Keep recipes and portioning as they are.',
            ];
        }

        $hours = $this->sales->grouped($filter, 'hour_only');
        if ($hours->count() > 0) {
            $peak = $hours->sortByDesc('net')->first();
            $slow = $hours->sortBy('net')->first();
            $insights[] = [
                'severity' => 'info',
                'title' => 'Peak hour is '.$this->hourLabel($peak->bucket).' ('.$this->money($peak->net).')',
                'body' => 'Slowest recorded hour is '.$this->hourLabel($slow->bucket).'.',
                'action' => 'Staff the peak; run prep and cleaning in the slow hour instead of adding labor at the peak.',
            ];
        }

        $purchases = (float) InventoryPurchase::query()
            ->whereBetween('received_date', [$filter->from->toDateString(), $filter->to->toDateString()])
            ->sum('total');
        if ($purchases > $current['net'] && $current['net'] > 0) {
            $insights[] = [
                'severity' => 'warning',
                'title' => 'Purchases ('.$this->money($purchases).') exceeded net sales',
                'body' => 'Buying more than you sold in this window ties up cash even if stock is needed.',
                'action' => 'Review the Purchases and Suppliers reports. Delay non-critical receiving if stock is already healthy.',
            ];
        }

        if ($current['aov'] > 0) {
            $itemsPerOrder = $current['orders'] > 0 ? $current['items_sold'] / $current['orders'] : 0;
            $insights[] = [
                'severity' => 'info',
                'title' => 'Average order value is '.$this->money($current['aov']),
                'body' => $current['items_sold'].' items across '.$current['orders'].' orders ('.round($itemsPerOrder, 2).' items per order).',
                'action' => $itemsPerOrder < 1.4
                    ? 'Train add-on prompts (drinks, raita, bread) on the POS.'
                    : 'AOV looks healthy. Protect it by not stacking discounts on already-full tickets.',
            ];
        }

        return array_slice($insights, 0, 12);
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2);
    }

    private function pct(?float $value): string
    {
        return $value === null ? 'n/a' : abs($value).'%';
    }

    private function hourLabel(mixed $bucket): string
    {
        $hour = str_pad((string) $bucket, 2, '0', STR_PAD_LEFT);

        return $hour.':00';
    }
}
