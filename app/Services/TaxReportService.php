<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Carbon;

class TaxReportService
{
    /**
     * @return array<string, mixed>
     */
    public function build(Carbon $from, Carbon $to): array
    {
        $orders = Order::query()
            ->with('items.menuItem.category')
            ->where('status', OrderStatus::Completed)
            ->whereBetween('completed_at', [$from, $to])
            ->orderBy('completed_at')
            ->get();

        $summary = [];
        $byRate = [];
        $byCategory = [];
        $byPayment = [];
        $collected = ['daily' => [], 'weekly' => [], 'monthly' => []];

        foreach ($orders as $order) {
            $snapshot = $order->tax_snapshot ?? [];
            $breakdown = $snapshot['breakdown'] ?? [];
            $taxable = (float) ($order->taxable_amount ?: ($snapshot['taxable_amount'] ?? 0));
            $taxAmount = (float) $order->tax_amount;
            $label = $order->tax_label ?: ($snapshot['label'] ?? 'Tax');
            $rate = (float) ($order->tax_percent ?: ($snapshot['headline_rate'] ?? 0));

            if ($breakdown === [] && $order->tax_applicable) {
                $breakdown = [[
                    'name' => $label.' '.$rate.'%',
                    'rate' => $rate,
                    'amount' => $taxAmount,
                ]];
            }

            if ($breakdown === []) {
                $summary[] = [
                    'date' => $order->completed_at?->toDateString(),
                    'invoice' => $order->invoice_number,
                    'order' => $order->id,
                    'taxable_sales' => $taxable,
                    'tax_name' => $order->tax_applicable ? $label : 'Not Applicable',
                    'tax_rate' => $rate,
                    'tax_amount' => $taxAmount,
                    'total_sales' => (float) $order->grand_total,
                ];
            } else {
                foreach ($breakdown as $row) {
                    $summary[] = [
                        'date' => $order->completed_at?->toDateString(),
                        'invoice' => $order->invoice_number,
                        'order' => $order->id,
                        'taxable_sales' => $taxable,
                        'tax_name' => $row['name'],
                        'tax_rate' => (float) $row['rate'],
                        'tax_amount' => (float) $row['amount'],
                        'total_sales' => (float) $order->grand_total,
                    ];
                    $rateKey = rtrim(rtrim(number_format((float) $row['rate'], 3, '.', ''), '0'), '.') ?: '0';
                    $byRate[$rateKey] = ($byRate[$rateKey] ?? 0) + (float) $row['amount'];
                }
            }

            foreach ($order->items as $item) {
                $category = $item->menuItem?->category?->name ?? 'Other';
                $byCategory[$category] = ($byCategory[$category] ?? 0) + (float) $item->tax_amount;
            }

            $method = $order->payment_method ?: 'Other';
            $byPayment[$method] = ($byPayment[$method] ?? 0) + $taxAmount;

            $day = $order->completed_at?->toDateString() ?? '';
            $week = $order->completed_at?->isoFormat('GGGG-[W]WW') ?? '';
            $month = $order->completed_at?->format('Y-m') ?? '';
            $collected['daily'][$day] = ($collected['daily'][$day] ?? 0) + $taxAmount;
            $collected['weekly'][$week] = ($collected['weekly'][$week] ?? 0) + $taxAmount;
            $collected['monthly'][$month] = ($collected['monthly'][$month] ?? 0) + $taxAmount;
        }

        ksort($byRate);
        ksort($collected['daily']);
        ksort($collected['weekly']);
        ksort($collected['monthly']);

        return compact('summary', 'byRate', 'byCategory', 'byPayment', 'collected');
    }
}
