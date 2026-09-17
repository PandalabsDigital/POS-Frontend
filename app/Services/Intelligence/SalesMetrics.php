<?php

namespace App\Services\Intelligence;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Setting;
use Illuminate\Support\Collection;

class SalesMetrics
{
    public function __construct(private OrderQuery $query) {}

    /**
     * @return array<string, mixed>
     */
    public function snapshot(ReportFilter $filter): array
    {
        $completed = $this->query->orders($filter, [OrderStatus::Completed]);
        $refunded = $this->query->orders($filter, [OrderStatus::Refunded]);
        $lineMode = $filter->categoryId || $filter->productId;

        if ($lineMode) {
            $gross = (float) $this->query->items($filter, [OrderStatus::Completed])->sum('order_items.line_total');
            $discounts = (float) $this->query->items($filter, [OrderStatus::Completed])
                ->selectRaw('COALESCE(SUM(orders.discount_amount * (order_items.line_total / NULLIF(orders.subtotal, 0))), 0) as allocated')
                ->value('allocated');
            $tax = (float) $this->query->items($filter, [OrderStatus::Completed])->sum('order_items.tax_amount');
            $itemsSold = (float) $this->query->items($filter, [OrderStatus::Completed])->sum('order_items.quantity');
            $refunds = (float) $this->query->items($filter, [OrderStatus::Refunded])->sum('order_items.line_total');
            $service = 0.0;
        } else {
            $gross = (float) (clone $completed)->sum('subtotal');
            $discounts = (float) (clone $completed)->sum('discount_amount');
            $tax = (float) (clone $completed)->sum('tax_amount');
            $service = (float) (clone $completed)->sum('service_charge_amount');
            $itemsSold = (float) $this->query->items($filter, [OrderStatus::Completed])->sum('order_items.quantity');
            $refunds = (float) (clone $refunded)->sum('grand_total');
        }

        $ordersCount = (int) (clone $completed)->count();
        $netBeforeRefunds = round($gross - $discounts, 2);
        $net = round($netBeforeRefunds - $refunds, 2);
        $aov = $ordersCount > 0 ? round($netBeforeRefunds / $ordersCount, 2) : 0.0;
        $itemsPerOrder = $ordersCount > 0 ? round($itemsSold / $ordersCount, 2) : 0.0;

        $payments = $this->payments($filter);
        $cashSales = (float) ($payments['cash']['amount'] ?? 0);
        $cashRefunds = (float) ($payments['cash']['refunds'] ?? 0);

        $capturedCustomers = (int) (clone $completed)
            ->whereNotNull('customer_phone')
            ->where('customer_phone', '!=', '')
            ->distinct()
            ->count('customer_phone');

        return [
            'restaurant' => Setting::restaurantName(),
            'branch' => Setting::restaurantName(),
            'branch_note' => 'This POS is configured as a single restaurant. Branch filter shows the current restaurant only.',
            'gross' => round($gross, 2),
            'discounts' => round($discounts, 2),
            'discount_percent' => $gross > 0 ? round(($discounts / $gross) * 100, 1) : 0.0,
            'refunds' => round($refunds, 2),
            'voids' => null,
            'net' => $net,
            'orders' => $ordersCount,
            'aov' => $aov,
            'items_sold' => $itemsSold,
            'customers' => $capturedCustomers,
            'tax' => round($tax, 2),
            'service_charges' => round($service, 2),
            'tips' => null,
            'delivery_fees' => null,
            'platform_commissions' => null,
            'cash_sales' => round($cashSales, 2),
            'card_sales' => round((float) ($payments['card']['amount'] ?? 0), 2),
            'upi_sales' => round((float) ($payments['upi']['amount'] ?? 0), 2),
            'other_payments' => round(
                (float) ($payments['online']['amount'] ?? 0)
                + (float) ($payments['wallet']['amount'] ?? 0)
                + (float) ($payments['other']['amount'] ?? 0),
                2
            ),
            'payments' => $payments,
            'cash' => [
                'opening' => null,
                'sales' => round($cashSales, 2),
                'refunds' => round($cashRefunds, 2),
                'expenses' => null,
                'adjustments' => null,
                'expected' => null,
                'counted' => null,
                'variance' => null,
            ],
            'unrecorded' => [
                'voids' => 'Item and order voids are not recorded. Refunded completed orders are tracked.',
                'tips' => 'Tips are not captured at payment.',
                'delivery_fees' => 'Delivery fees are not a separate order field.',
                'platform_commissions' => 'Aggregator commissions are not recorded.',
                'cash_drawer' => 'Opening float, counted cash, and cash payouts are not recorded, so expected cash and variance cannot be calculated.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function compared(ReportFilter $filter): array
    {
        $current = $this->snapshot($filter);
        $previous = $this->snapshot(new ReportFilter(
            from: $filter->compareFrom,
            to: $filter->compareTo,
            preset: $filter->preset,
            compare: 'none',
            compareFrom: $filter->compareFrom,
            compareTo: $filter->compareTo,
            orderType: $filter->orderType,
            paymentMethod: $filter->paymentMethod,
            employeeId: $filter->employeeId,
            categoryId: $filter->categoryId,
            productId: $filter->productId,
            channel: $filter->channel,
        ));

        $keys = ['gross', 'discounts', 'refunds', 'net', 'orders', 'aov', 'items_sold', 'tax', 'service_charges', 'cash_sales'];
        $delta = [];
        foreach ($keys as $key) {
            $delta[$key] = $this->delta((float) $current[$key], (float) $previous[$key]);
        }

        return ['current' => $current, 'previous' => $previous, 'delta' => $delta];
    }

    /**
     * @return array<string, array{transactions: int, amount: float, refunds: float, net: float}>
     */
    public function payments(ReportFilter $filter): array
    {
        $methods = config('taxation.payment_methods');
        $rows = [];

        foreach ($methods as $code => $label) {
            $sales = $this->query->orders($filter, [OrderStatus::Completed])->where('payment_method', $code);
            $refunds = $this->query->orders($filter, [OrderStatus::Refunded])->where('payment_method', $code);
            $amount = (float) (clone $sales)->sum('grand_total');
            $refundAmount = (float) (clone $refunds)->sum('grand_total');
            $rows[$code] = [
                'label' => $label,
                'transactions' => (int) (clone $sales)->count(),
                'amount' => round($amount, 2),
                'refunds' => round($refundAmount, 2),
                'net' => round($amount - $refundAmount, 2),
                'expected' => round($amount, 2),
                'recorded' => round($amount, 2),
                'settlement' => null,
            ];
        }

        $unknownSales = $this->query->orders($filter, [OrderStatus::Completed])->whereNull('payment_method');
        if ((clone $unknownSales)->exists()) {
            $amount = (float) (clone $unknownSales)->sum('grand_total');
            $rows['unspecified'] = [
                'label' => 'Unspecified',
                'transactions' => (int) (clone $unknownSales)->count(),
                'amount' => round($amount, 2),
                'refunds' => 0.0,
                'net' => round($amount, 2),
                'expected' => round($amount, 2),
                'recorded' => round($amount, 2),
                'settlement' => null,
            ];
        }

        return $rows;
    }

    public function grouped(ReportFilter $filter, string $grain): Collection
    {
        $expr = $this->query->groupExpression($grain);

        return $this->query->orders($filter, [OrderStatus::Completed])
            ->selectRaw("{$expr} as bucket")
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('SUM(subtotal) as gross')
            ->selectRaw('SUM(discount_amount) as discounts')
            ->selectRaw('SUM(tax_amount) as tax')
            ->selectRaw('SUM(subtotal - discount_amount) as net')
            ->selectRaw('SUM(grand_total) as collected')
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->map(function ($row) {
                $orders = (int) $row->orders_count;
                $net = (float) $row->net;

                return (object) [
                    'bucket' => $row->bucket,
                    'orders' => $orders,
                    'gross' => round((float) $row->gross, 2),
                    'discounts' => round((float) $row->discounts, 2),
                    'refunds' => 0.0,
                    'net' => round($net, 2),
                    'tax' => round((float) $row->tax, 2),
                    'aov' => $orders > 0 ? round($net / $orders, 2) : 0.0,
                ];
            });
    }

    public function byOrderType(ReportFilter $filter): Collection
    {
        $rows = $this->query->orders($filter, [OrderStatus::Completed])
            ->selectRaw('type, COUNT(*) as orders_count, SUM(subtotal) as gross, SUM(discount_amount) as discounts, SUM(subtotal - discount_amount) as net, SUM(tax_amount) as tax')
            ->groupBy('type')
            ->get()
            ->keyBy(fn ($row) => $row->type instanceof OrderType ? $row->type->value : (string) $row->type);

        return collect(OrderType::cases())->map(function (OrderType $type) use ($rows) {
            $row = $rows->get($type->value);
            $orders = (int) ($row->orders_count ?? 0);
            $net = (float) ($row->net ?? 0);

            return (object) [
                'key' => $type->value,
                'label' => $type->label(),
                'orders' => $orders,
                'gross' => round((float) ($row->gross ?? 0), 2),
                'discounts' => round((float) ($row->discounts ?? 0), 2),
                'net' => round($net, 2),
                'tax' => round((float) ($row->tax ?? 0), 2),
                'aov' => $orders > 0 ? round($net / $orders, 2) : 0.0,
            ];
        });
    }

    public function byChannel(ReportFilter $filter): Collection
    {
        $orders = $this->query->orders($filter, [OrderStatus::Completed])->get(['type', 'payment_method', 'subtotal', 'discount_amount', 'grand_total']);
        $buckets = [
            'pos' => ['label' => 'POS / in-house', 'orders' => 0, 'net' => 0.0, 'gross' => 0.0],
            'delivery' => ['label' => 'Delivery', 'orders' => 0, 'net' => 0.0, 'gross' => 0.0],
            'online' => ['label' => 'Online payment', 'orders' => 0, 'net' => 0.0, 'gross' => 0.0],
        ];

        foreach ($orders as $order) {
            $channel = $order->type === OrderType::Delivery
                ? 'delivery'
                : ($order->payment_method === 'online' ? 'online' : 'pos');
            $buckets[$channel]['orders']++;
            $buckets[$channel]['gross'] += (float) $order->subtotal;
            $buckets[$channel]['net'] += (float) $order->subtotal - (float) $order->discount_amount;
        }

        return collect($buckets)->map(fn ($row, $key) => (object) [
            'key' => $key,
            'label' => $row['label'],
            'orders' => $row['orders'],
            'gross' => round($row['gross'], 2),
            'net' => round($row['net'], 2),
            'aov' => $row['orders'] > 0 ? round($row['net'] / $row['orders'], 2) : 0.0,
        ])->values();
    }

    public function byEmployee(ReportFilter $filter): Collection
    {
        return $this->query->orders($filter, [OrderStatus::Completed])
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->selectRaw('users.id as employee_id, users.name as employee_name, COUNT(orders.id) as orders_count, SUM(orders.subtotal) as gross, SUM(orders.discount_amount) as discounts, SUM(orders.subtotal - orders.discount_amount) as net, SUM(orders.tax_amount) as tax')
            ->groupBy('users.id', 'users.name')
            ->get()
            ->map(function ($row) {
                $orders = (int) $row->orders_count;
                $gross = (float) $row->gross;
                $net = (float) $row->net;

                return (object) [
                    'employee_id' => $row->employee_id,
                    'name' => $row->employee_name ?: 'Unknown',
                    'orders' => $orders,
                    'gross' => round($gross, 2),
                    'discounts' => round((float) $row->discounts, 2),
                    'net' => round($net, 2),
                    'tax' => round((float) $row->tax, 2),
                    'aov' => $orders > 0 ? round($net / $orders, 2) : 0.0,
                    'avg_discount' => $gross > 0 ? round(((float) $row->discounts / $gross) * 100, 1) : 0.0,
                ];
            })
            ->sortByDesc('net')
            ->values();
    }

    public function shifts(ReportFilter $filter): Collection
    {
        $expr = $this->query->groupExpression('day');

        return $this->query->orders($filter, [OrderStatus::Completed])
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->selectRaw("{$expr} as work_date, users.id as employee_id, users.name as employee_name, COUNT(orders.id) as orders_count, SUM(orders.subtotal - orders.discount_amount) as net, SUM(orders.discount_amount) as discounts, MIN(orders.completed_at) as first_sale, MAX(orders.completed_at) as last_sale")
            ->groupByRaw("{$expr}, users.id, users.name")
            ->orderBy('work_date')
            ->get()
            ->map(fn ($row) => (object) [
                'date' => $row->work_date,
                'employee' => $row->employee_name ?: 'Unknown',
                'orders' => (int) $row->orders_count,
                'net' => round((float) $row->net, 2),
                'discounts' => round((float) $row->discounts, 2),
                'discount_percent' => ((float) $row->net + (float) $row->discounts) > 0
                    ? round(((float) $row->discounts / ((float) $row->net + (float) $row->discounts)) * 100, 1)
                    : 0.0,
                'first_sale' => $row->first_sale,
                'last_sale' => $row->last_sale,
            ]);
    }

    public function refunds(ReportFilter $filter): Collection
    {
        return $this->query->orders($filter, [OrderStatus::Refunded])
            ->with(['cashier', 'items'])
            ->latest('completed_at')
            ->get();
    }

    public function discountedOrders(ReportFilter $filter): Collection
    {
        return $this->query->orders($filter, [OrderStatus::Completed])
            ->where('discount_amount', '>', 0)
            ->with(['cashier', 'items.menuItem.category'])
            ->latest('completed_at')
            ->get();
    }

    public function history(ReportFilter $filter, int $perPage = 30)
    {
        return $this->query->orders($filter, null)
            ->with('cashier')
            ->latest('completed_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array{amount: float, percent: ?float}
     */
    public function delta(float $current, float $previous): array
    {
        $amount = round($current - $previous, 2);
        $percent = $previous == 0.0
            ? ($current == 0.0 ? 0.0 : null)
            : round((($current - $previous) / abs($previous)) * 100, 1);

        return ['amount' => $amount, 'percent' => $percent];
    }

    public function customers(ReportFilter $filter): array
    {
        $completed = $this->query->orders($filter, [OrderStatus::Completed]);
        $captured = (clone $completed)->whereNotNull('customer_phone')->where('customer_phone', '!=', '');
        $declined = (int) (clone $completed)->where('customer_capture_status', 'customer_declined')->count();
        $unique = (int) (clone $captured)->distinct()->count('customer_phone');
        $repeat = (clone $captured)
            ->select('customer_phone')
            ->groupBy('customer_phone')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        $top = (clone $captured)
            ->selectRaw('customer_name, customer_phone, COUNT(*) as visits, SUM(grand_total) as spent')
            ->groupBy('customer_name', 'customer_phone')
            ->orderByDesc('spent')
            ->limit(50)
            ->get();

        return [
            'orders' => (int) (clone $completed)->count(),
            'unique' => $unique,
            'repeat' => $repeat,
            'declined' => $declined,
            'top' => $top,
        ];
    }
}
