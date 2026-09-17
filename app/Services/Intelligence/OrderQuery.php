<?php

namespace App\Services\Intelligence;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OrderQuery
{
    /**
     * @param  array<int, OrderStatus|string>|null  $statuses
     */
    public function orders(ReportFilter $filter, ?array $statuses = [OrderStatus::Completed]): Builder
    {
        $query = Order::query()->whereBetween('completed_at', [$filter->from, $filter->to]);

        if ($statuses !== null) {
            $query->whereIn('status', $statuses);
        }

        $this->applyOrderFilters($query, $filter);

        if ($filter->categoryId || $filter->productId) {
            $query->whereHas('items', function (Builder $items) use ($filter): void {
                $this->applyItemFilters($items, $filter);
            });
        }

        return $query;
    }

    /**
     * @param  array<int, OrderStatus|string>  $statuses
     */
    public function items(ReportFilter $filter, array $statuses = [OrderStatus::Completed]): Builder
    {
        $query = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.completed_at', [$filter->from, $filter->to])
            ->whereIn('orders.status', $statuses);

        $this->applyOrderFilters($query, $filter, 'orders');
        $this->applyItemFilters($query, $filter);

        return $query;
    }

    public function applyOrderFilters(Builder $query, ReportFilter $filter, string $table = 'orders'): void
    {
        if ($filter->orderType) {
            $query->where($table.'.type', $filter->orderType);
        }

        if ($filter->paymentMethod) {
            $query->where($table.'.payment_method', $filter->paymentMethod);
        }

        if ($filter->employeeId) {
            $query->where($table.'.user_id', $filter->employeeId);
        }

        if ($filter->channel === 'delivery') {
            $query->where($table.'.type', 'delivery');
        } elseif ($filter->channel === 'online') {
            $query->where($table.'.payment_method', 'online');
        } elseif ($filter->channel === 'pos') {
            $query->where($table.'.type', '!=', 'delivery')
                ->where(function (Builder $inner) use ($table): void {
                    $inner->whereNull($table.'.payment_method')
                        ->orWhere($table.'.payment_method', '!=', 'online');
                });
        }
    }

    public function applyItemFilters(Builder $query, ReportFilter $filter): void
    {
        if ($filter->productId) {
            $query->where('order_items.menu_item_id', $filter->productId);
        }

        if ($filter->categoryId) {
            $query->whereExists(function ($sub) use ($filter): void {
                $sub->select(DB::raw(1))
                    ->from('menu_items')
                    ->whereColumn('menu_items.id', 'order_items.menu_item_id')
                    ->where('menu_items.category_id', $filter->categoryId);
            });
        }
    }

    public function groupExpression(string $grain, string $column = 'orders.completed_at'): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return match ($grain) {
                'hour' => "strftime('%Y-%m-%d %H:00', {$column})",
                'hour_only' => "strftime('%H', {$column})",
                'weekday' => "strftime('%w', {$column})",
                'week' => "strftime('%Y-%W', {$column})",
                'month' => "strftime('%Y-%m', {$column})",
                default => "DATE({$column})",
            };
        }

        return match ($grain) {
            'hour' => "DATE_FORMAT({$column}, '%Y-%m-%d %H:00')",
            'hour_only' => "DATE_FORMAT({$column}, '%H')",
            'weekday' => "DAYOFWEEK({$column}) - 1",
            'week' => "DATE_FORMAT({$column}, '%x-%v')",
            'month' => "DATE_FORMAT({$column}, '%Y-%m')",
            default => "DATE({$column})",
        };
    }
}
