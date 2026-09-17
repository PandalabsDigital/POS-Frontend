<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use Illuminate\Support\Carbon;

class DashboardService
{
    /**
     * @return array{
     *     todayRevenue: float,
     *     todayOrders: int,
     *     totalProducts: int,
     *     totalCategories: int,
     *     chartLabels: array<int, string>,
     *     chartValues: array<int, float>
     * }
     */
    public function stats(): array
    {
        $today = Carbon::today();

        $todayOrders = Order::query()
            ->where('status', OrderStatus::Completed)
            ->whereDate('completed_at', $today);

        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $labels[] = $day->format('D');
            $values[] = (float) Order::query()
                ->where('status', OrderStatus::Completed)
                ->whereDate('completed_at', $day)
                ->sum('grand_total');
        }

        return [
            'todayRevenue' => (float) (clone $todayOrders)->sum('grand_total'),
            'todayOrders' => (clone $todayOrders)->count(),
            'totalProducts' => MenuItem::query()->count(),
            'totalCategories' => Category::query()->count(),
            'chartLabels' => $labels,
            'chartValues' => $values,
        ];
    }
}
