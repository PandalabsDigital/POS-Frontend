<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * @return array{from: Carbon, to: Carbon}
     */
    public function range(?string $from, ?string $to): array
    {
        $start = $from ? Carbon::parse($from)->startOfDay() : Carbon::today()->startOfMonth();
        $end = $to ? Carbon::parse($to)->endOfDay() : Carbon::today()->endOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return ['from' => $start, 'to' => $end];
    }

    public function dailySales(Carbon $from, Carbon $to): Collection
    {
        return Order::query()
            ->selectRaw('DATE(completed_at) as sale_date, COUNT(*) as orders_count, SUM(grand_total) as revenue')
            ->where('status', OrderStatus::Completed)
            ->whereBetween('completed_at', [$from, $to])
            ->groupByRaw('DATE(completed_at)')
            ->orderBy('sale_date')
            ->get();
    }

    public function monthlySales(Carbon $from, Carbon $to): Collection
    {
        return Order::query()
            ->selectRaw("strftime('%Y-%m', completed_at) as sale_month, COUNT(*) as orders_count, SUM(grand_total) as revenue")
            ->where('status', OrderStatus::Completed)
            ->whereBetween('completed_at', [$from, $to])
            ->groupBy('sale_month')
            ->orderBy('sale_month')
            ->get();
    }

    /**
     * MySQL-compatible monthly grouping when not using SQLite.
     */
    public function monthlySalesForDriver(Carbon $from, Carbon $to, string $driver): Collection
    {
        if ($driver === 'sqlite') {
            return $this->monthlySales($from, $to);
        }

        return Order::query()
            ->selectRaw("DATE_FORMAT(completed_at, '%Y-%m') as sale_month, COUNT(*) as orders_count, SUM(grand_total) as revenue")
            ->where('status', OrderStatus::Completed)
            ->whereBetween('completed_at', [$from, $to])
            ->groupBy('sale_month')
            ->orderBy('sale_month')
            ->get();
    }

    public function orderHistory(Carbon $from, Carbon $to): LengthAwarePaginator
    {
        return Order::query()
            ->with('cashier')
            ->whereBetween('completed_at', [$from, $to])
            ->latest('completed_at')
            ->paginate(20)
            ->withQueryString();
    }
}
