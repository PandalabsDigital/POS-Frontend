<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryBatch;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventorySupplier;
use App\Models\InventoryUnit;
use App\Services\InventoryAnalytics;
use App\Services\InventorySettings;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(InventoryAnalytics $analytics): View
    {
        $settings = InventorySettings::current();

        return view('inventory.dashboard', [
            'stats' => $analytics->dashboard(),
            'settings' => $settings,
            'categories' => InventoryCategory::query()->active()->count(),
            'items' => InventoryItem::query()->active()->count(),
            'suppliers' => InventorySupplier::query()->active()->count(),
            'locations' => InventoryLocation::query()->active()->get(),
            'units' => InventoryUnit::query()->count(),
            'expired' => InventoryBatch::query()
                ->where('quantity', '>', 0)
                ->whereDate('expires_at', '<', now()->toDateString())
                ->count(),
        ]);
    }
}
