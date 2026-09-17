<?php

namespace App\Http\Controllers\Intelligence;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Models\User;
use App\Services\Intelligence\InsightEngine;
use App\Services\Intelligence\ProductAnalytics;
use App\Services\Intelligence\ReportFilter;
use App\Services\Intelligence\SalesMetrics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __construct(
        private SalesMetrics $sales,
        private ProductAnalytics $products,
        private InsightEngine $insights,
    ) {}

    public function index(Request $request): View
    {
        $filter = ReportFilter::fromRequest($request);
        $compared = $this->sales->compared($filter);
        $products = $this->products->products($filter);
        $hourly = $this->sales->grouped($filter, 'hour_only');
        $daily = $this->sales->grouped($filter, 'day');

        return view('intelligence.analytics.index', [
            'filter' => $filter,
            'filters' => [
                'restaurant' => Setting::restaurantName(),
                'employees' => User::query()->orderBy('name')->get(['id', 'name']),
                'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
                'products' => MenuItem::query()->orderBy('name')->get(['id', 'name']),
                'payments' => config('taxation.payment_methods'),
            ],
            'compared' => $compared,
            'insights' => $this->insights->build($filter, $compared),
            'products' => $products,
            'hourly' => $hourly,
            'daily' => $daily,
            'types' => $this->sales->byOrderType($filter),
            'channels' => $this->sales->byChannel($filter),
            'engineering' => [
                'star' => $products->where('classification', 'star')->values(),
                'plow_horse' => $products->where('classification', 'plow_horse')->values(),
                'puzzle' => $products->where('classification', 'puzzle')->values(),
                'dog' => $products->where('classification', 'dog')->values(),
                'uncosted' => $products->where('classification', 'uncosted')->values(),
            ],
        ]);
    }
}
