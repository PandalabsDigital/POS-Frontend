<?php

namespace App\Services\Intelligence;

use App\Enums\OrderStatus;
use App\Models\InventoryRecipe;
use App\Services\RecipeCosting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductAnalytics
{
    public function __construct(
        private OrderQuery $query,
        private RecipeCosting $costing,
    ) {}

    /**
     * @return Collection<int, object>
     */
    public function products(ReportFilter $filter, string $sort = 'revenue'): Collection
    {
        $costs = $this->portionCosts();
        $sales = $this->query->items($filter, [OrderStatus::Completed])
            ->leftJoin('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->leftJoin('categories', 'categories.id', '=', 'menu_items.category_id')
            ->select([
                'order_items.menu_item_id',
                DB::raw('MAX(order_items.name) as name'),
                DB::raw('MAX(categories.id) as category_id'),
                DB::raw('MAX(categories.name) as category_name'),
                DB::raw('SUM(order_items.quantity) as units'),
                DB::raw('SUM(order_items.line_total) as revenue'),
                DB::raw('SUM(orders.discount_amount * (order_items.line_total / NULLIF(orders.subtotal, 0))) as discounts'),
                DB::raw('SUM(order_items.line_total) / NULLIF(SUM(order_items.quantity), 0) as avg_price'),
            ])
            ->groupBy('order_items.menu_item_id')
            ->get();

        $refunds = $this->query->items($filter, [OrderStatus::Refunded])
            ->selectRaw('order_items.menu_item_id, SUM(order_items.line_total) as refunds, SUM(order_items.quantity) as units')
            ->groupBy('order_items.menu_item_id')
            ->get()
            ->keyBy('menu_item_id');

        $rows = $sales->map(function ($row) use ($costs, $refunds) {
            $id = $row->menu_item_id;
            $units = (float) $row->units;
            $revenue = (float) $row->revenue;
            $discounts = (float) ($row->discounts ?? 0);
            $refundAmount = (float) ($refunds[$id]->refunds ?? 0);
            $net = round($revenue - $discounts - $refundAmount, 2);
            $portion = $costs[$id] ?? null;
            $foodCost = $portion !== null ? round($portion * $units, 2) : null;
            $profit = $foodCost !== null ? round($net - $foodCost, 2) : null;
            $margin = ($profit !== null && $net > 0) ? round(($profit / $net) * 100, 1) : null;

            return (object) [
                'menu_item_id' => $id,
                'name' => $row->name,
                'category_id' => $row->category_id,
                'category' => $row->category_name ?: 'Uncategorized',
                'units' => $units,
                'revenue' => round($revenue, 2),
                'discounts' => round($discounts, 2),
                'discount_percent' => $revenue > 0 ? round(($discounts / $revenue) * 100, 1) : 0.0,
                'refunds' => round($refundAmount, 2),
                'net' => $net,
                'food_cost' => $foodCost,
                'gross_profit' => $profit,
                'margin' => $margin,
                'avg_price' => round((float) $row->avg_price, 2),
                'classification' => 'uncosted',
            ];
        });

        $costed = $rows->filter(fn ($row) => $row->margin !== null);
        $unitMedian = $this->median($rows->pluck('units')->all());
        $marginMedian = $this->median($costed->pluck('margin')->all());

        $rows = $rows->map(function ($row) use ($unitMedian, $marginMedian) {
            $row->classification = $this->classify($row, $unitMedian, $marginMedian);

            return $row;
        });

        return $this->sort($rows, $sort);
    }

    /**
     * @return Collection<int, object>
     */
    public function categories(ReportFilter $filter): Collection
    {
        $products = $this->products($filter, 'revenue');
        $totalNet = (float) $products->sum('net');

        return $products->groupBy('category')->map(function (Collection $items, string $category) use ($totalNet) {
            $net = (float) $items->sum('net');
            $food = $items->sum(fn ($row) => $row->food_cost ?? 0);
            $costedNet = (float) $items->filter(fn ($row) => $row->food_cost !== null)->sum('net');
            $profit = $items->sum(fn ($row) => $row->gross_profit ?? 0);

            return (object) [
                'name' => $category,
                'category_id' => $items->first()->category_id ?? null,
                'revenue' => round((float) $items->sum('revenue'), 2),
                'units' => (float) $items->sum('units'),
                'share' => $totalNet > 0 ? round(($net / $totalNet) * 100, 1) : 0.0,
                'food_cost' => round($food, 2),
                'gross_profit' => round($profit, 2),
                'margin' => $costedNet > 0 ? round(($profit / $costedNet) * 100, 1) : null,
                'products' => $items->values(),
            ];
        })->sortByDesc('revenue')->values();
    }

    public function productOrders(ReportFilter $filter)
    {
        return $this->query->orders($filter, [OrderStatus::Completed, OrderStatus::Refunded])
            ->with(['cashier', 'items'])
            ->latest('completed_at')
            ->paginate(40)
            ->withQueryString();
    }

    /**
     * @return array<int, float>
     */
    public function portionCosts(): array
    {
        $costs = [];
        $recipes = InventoryRecipe::query()
            ->where('type', 'sale')
            ->whereNotNull('menu_item_id')
            ->where('is_active', true)
            ->with(['ingredients.item.stockUnit', 'ingredients.unit'])
            ->get();

        foreach ($recipes as $recipe) {
            $costs[(int) $recipe->menu_item_id] = $this->costing->summarize($recipe)['portion_cost'];
        }

        return $costs;
    }

    /**
     * @param  list<float|int>  $values
     */
    private function median(array $values): float
    {
        $values = array_values(array_map('floatval', $values));
        $count = count($values);
        if ($count === 0) {
            return 0.0;
        }
        sort($values);
        $mid = intdiv($count, 2);

        return $count % 2 ? $values[$mid] : ($values[$mid - 1] + $values[$mid]) / 2;
    }

    private function classify(object $row, float $unitMedian, float $marginMedian): string
    {
        if ($row->margin === null) {
            return 'uncosted';
        }

        $highSales = $row->units >= $unitMedian;
        $highMargin = $row->margin >= $marginMedian;

        return match (true) {
            $highSales && $highMargin => 'star',
            $highSales && ! $highMargin => 'plow_horse',
            ! $highSales && $highMargin => 'puzzle',
            default => 'dog',
        };
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    private function sort(Collection $rows, string $sort): Collection
    {
        $sorted = match ($sort) {
            'units', 'best_sellers' => $rows->sortByDesc('units'),
            'profit' => $rows->sortByDesc(fn ($row) => $row->gross_profit ?? -INF),
            'margin' => $rows->sortByDesc(fn ($row) => $row->margin ?? -INF),
            'lowest_margin' => $rows->sortBy(fn ($row) => $row->margin ?? INF),
            'lowest_sellers' => $rows->sortBy('units'),
            default => $rows->sortByDesc('revenue'),
        };

        return $sorted->values();
    }
}
