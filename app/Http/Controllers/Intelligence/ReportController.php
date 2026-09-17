<?php

namespace App\Http\Controllers\Intelligence;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Models\User;
use App\Services\Intelligence\InventoryIntel;
use App\Services\Intelligence\ProductAnalytics;
use App\Services\Intelligence\ReportCatalog;
use App\Services\Intelligence\ReportFilter;
use App\Services\Intelligence\SalesMetrics;
use App\Services\TaxReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private SalesMetrics $sales,
        private ProductAnalytics $products,
        private InventoryIntel $inventory,
        private TaxReportService $taxReports,
    ) {}

    public function index(): View
    {
        return view('intelligence.reports.hub', [
            'reports' => ReportCatalog::reports(),
        ]);
    }

    public function show(Request $request, string $report): View|StreamedResponse
    {
        $catalog = ReportCatalog::reports();
        abort_unless(array_key_exists($report, $catalog), 404);

        $filter = ReportFilter::fromRequest($request);
        $payload = $this->payload($request, $report, $filter);

        if ($request->query('export') === 'csv') {
            return $this->csv($report, $payload);
        }

        return view('intelligence.reports.'.$this->viewName($report), array_merge($payload, [
            'report' => $report,
            'meta' => $catalog[$report],
            'filter' => $filter,
            'filters' => $this->filterOptions(),
            'reports' => $catalog,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request, string $report, ReportFilter $filter): array
    {
        $sort = (string) $request->query('sort', 'revenue');

        return match ($report) {
            'daily' => $this->dailyPayload($filter),
            'sales' => [
                'compared' => $this->sales->compared($filter),
                'daily' => $this->sales->grouped($filter, 'day'),
                'weekly' => $this->sales->grouped($filter, 'week'),
                'monthly' => $this->sales->grouped($filter, 'month'),
                'hourly' => $this->sales->grouped($filter, 'hour_only'),
                'weekday' => $this->sales->grouped($filter, 'weekday'),
                'types' => $this->sales->byOrderType($filter),
                'channels' => $this->sales->byChannel($filter),
                'payments' => $this->sales->payments($filter),
                'history' => $this->sales->history($filter),
            ],
            'products' => [
                'rows' => $this->products->products($filter, $sort),
                'sort' => $sort,
            ],
            'categories' => [
                'rows' => $this->products->categories($filter),
            ],
            'payments' => [
                'payments' => $this->sales->payments($filter),
                'compared' => $this->sales->compared($filter),
            ],
            'discounts' => $this->discountPayload($filter),
            'refunds' => [
                'rows' => $this->sales->refunds($filter),
                'compared' => $this->sales->compared($filter),
            ],
            'shifts' => [
                'rows' => $this->sales->shifts($filter),
            ],
            'employees' => [
                'rows' => $this->sales->byEmployee($filter),
            ],
            'customers' => $this->sales->customers($filter),
            'tax' => $this->taxReports->build($filter->from, $filter->to),
            'inventory' => $this->inventory->summary($filter),
            'wastage' => (function () use ($filter) {
                $rows = $this->inventory->wastage($filter);

                return ['rows' => $rows, 'total' => (float) $rows->sum('cost')];
            })(),
            'variance' => [
                'rows' => $this->inventory->variance($filter),
            ],
            'purchases' => [
                'rows' => $this->inventory->purchases($filter),
            ],
            'suppliers' => [
                'rows' => $this->inventory->suppliers($filter),
            ],
            'delivery' => [
                'channels' => $this->sales->byChannel($filter),
                'types' => $this->sales->byOrderType($filter),
            ],
            'profitability' => $this->profitability($filter),
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function dailyPayload(ReportFilter $filter): array
    {
        $today = $this->cloneFilter($filter, now()->startOfDay(), now()->endOfDay());
        $yesterday = $this->cloneFilter($filter, now()->subDay()->startOfDay(), now()->subDay()->endOfDay());
        $weekAgo = $this->cloneFilter($filter, now()->subWeek()->startOfDay(), now()->subWeek()->endOfDay());
        $yearAgo = $this->cloneFilter($filter, now()->subYear()->startOfDay(), now()->subYear()->endOfDay());

        return [
            'compared' => $this->sales->compared($filter),
            'today' => $this->sales->snapshot($today),
            'yesterday' => $this->sales->snapshot($yesterday),
            'weekday' => $this->sales->snapshot($weekAgo),
            'lastYear' => $this->sales->snapshot($yearAgo),
        ];
    }

    private function cloneFilter(ReportFilter $filter, $from, $to): ReportFilter
    {
        return new ReportFilter(
            from: $from,
            to: $to,
            preset: 'custom',
            compare: 'none',
            compareFrom: $from,
            compareTo: $to,
            orderType: $filter->orderType,
            paymentMethod: $filter->paymentMethod,
            employeeId: $filter->employeeId,
            categoryId: $filter->categoryId,
            productId: $filter->productId,
            channel: $filter->channel,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function discountPayload(ReportFilter $filter): array
    {
        $orders = $this->sales->discountedOrders($filter);
        $employees = $this->sales->byEmployee($filter);
        $avg = (float) $employees->avg('avg_discount');
        $flags = $employees
            ->filter(fn ($row) => $avg > 0 && $row->avg_discount >= $avg * 2 && $row->discounts > 0)
            ->map(fn ($row) => (object) [
                'name' => $row->name,
                'multiple' => round($row->avg_discount / $avg, 1),
                'avg_discount' => $row->avg_discount,
            ]);

        $byProduct = $this->products->products($filter)
            ->filter(fn ($row) => $row->discounts > 0)
            ->sortByDesc('discounts')
            ->values();

        $snapshot = $this->sales->snapshot($filter);

        return [
            'orders' => $orders,
            'employees' => $employees,
            'flags' => $flags,
            'products' => $byProduct,
            'total' => (float) $orders->sum('discount_amount'),
            'count' => $orders->count(),
            'avg_employee_discount' => $avg,
            'rate' => $snapshot['discount_percent'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function profitability(ReportFilter $filter): array
    {
        $compared = $this->sales->compared($filter);
        $products = $this->products->products($filter);
        $recipeCost = (float) $products->sum(fn ($row) => $row->food_cost ?? 0);
        $costedNet = (float) $products->filter(fn ($row) => $row->food_cost !== null)->sum('net');
        $inventory = $this->inventory->summary($filter);
        $waste = $inventory['waste'];
        $grossProfit = round($costedNet - $recipeCost, 2);

        return [
            'compared' => $compared,
            'recipe_cost' => round($recipeCost, 2),
            'costed_net' => round($costedNet, 2),
            'waste' => $waste,
            'consumption' => $inventory['consumption'],
            'purchases' => $inventory['purchases'],
            'gross_profit' => $grossProfit,
            'margin' => $costedNet > 0 ? round(($grossProfit / $costedNet) * 100, 1) : null,
            'uncosted' => $products->where('food_cost', null)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function filterOptions(): array
    {
        return [
            'restaurant' => Setting::restaurantName(),
            'employees' => User::query()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'products' => MenuItem::query()->orderBy('name')->get(['id', 'name']),
            'payments' => config('taxation.payment_methods'),
        ];
    }

    private function viewName(string $report): string
    {
        return in_array($report, ['expenses', 'tables'], true) ? 'unavailable' : $report;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function csv(string $report, array $payload): StreamedResponse
    {
        $filename = 'restassured-'.$report.'-'.now()->toDateString().'.csv';

        return response()->streamDownload(function () use ($report, $payload): void {
            $out = fopen('php://output', 'w');
            match ($report) {
                'products' => $this->writeRows($out, ['Product', 'Category', 'Units', 'Revenue', 'Discounts', 'Refunds', 'Net', 'Food cost', 'Profit', 'Margin %'], $payload['rows']->map(fn ($row) => [
                    $row->name, $row->category, $row->units, $row->revenue, $row->discounts, $row->refunds, $row->net, $row->food_cost, $row->gross_profit, $row->margin,
                ])),
                'sales' => $this->writeRows($out, ['Day', 'Orders', 'Gross', 'Discounts', 'Net', 'Tax', 'AOV'], $payload['daily']->map(fn ($row) => [
                    $row->bucket, $row->orders, $row->gross, $row->discounts, $row->net, $row->tax, $row->aov,
                ])),
                'employees' => $this->writeRows($out, ['Employee', 'Orders', 'Gross', 'Discounts', 'Net', 'AOV'], $payload['rows']->map(fn ($row) => [
                    $row->name, $row->orders, $row->gross, $row->discounts, $row->net, $row->aov,
                ])),
                'payments' => $this->writeRows($out, ['Method', 'Transactions', 'Amount', 'Refunds', 'Net'], collect($payload['payments'])->map(fn ($row) => [
                    $row['label'], $row['transactions'], $row['amount'], $row['refunds'], $row['net'],
                ])),
                'wastage' => $this->writeRows($out, ['When', 'Item', 'Qty', 'Cost', 'Reason'], $payload['rows']->map(fn ($row) => [
                    $row->occurred_at, $row->item?->name, $row->quantity, $row->cost, $row->reason,
                ])),
                'purchases' => $this->writeRows($out, ['Invoice', 'Supplier', 'Received', 'Total'], $payload['rows']->map(fn ($row) => [
                    $row->invoice_number, $row->supplier?->name, $row->received_date, $row->total,
                ])),
                default => $this->writeRows($out, ['Metric', 'Value'], collect([
                    ['Gross', $payload['compared']['current']['gross'] ?? ''],
                    ['Discounts', $payload['compared']['current']['discounts'] ?? ''],
                    ['Refunds', $payload['compared']['current']['refunds'] ?? ''],
                    ['Net', $payload['compared']['current']['net'] ?? ''],
                ])),
            };
            fclose($out);
        }, $filename);
    }

    /**
     * @param  resource  $out
     */
    private function writeRows($out, array $headers, $rows): void
    {
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, is_array($row) ? $row : (array) $row);
        }
    }
}
