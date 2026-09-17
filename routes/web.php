<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CondimentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Inventory\AdjustmentController as InventoryAdjustmentController;
use App\Http\Controllers\Inventory\CatalogExtrasController;
use App\Http\Controllers\Inventory\CategoryController as InventoryCategoryController;
use App\Http\Controllers\Inventory\DashboardController as InventoryDashboardController;
use App\Http\Controllers\Inventory\ItemController as InventoryItemController;
use App\Http\Controllers\Inventory\PurchaseController as InventoryPurchaseController;
use App\Http\Controllers\Inventory\RecipeController as InventoryRecipeController;
use App\Http\Controllers\Inventory\ReportController as InventoryReportController;
use App\Http\Controllers\Inventory\StockController as InventoryStockController;
use App\Http\Controllers\Inventory\StocktakeController as InventoryStocktakeController;
use App\Http\Controllers\Inventory\SupplierController as InventorySupplierController;
use App\Http\Controllers\Inventory\TransferController as InventoryTransferController;
use App\Http\Controllers\Inventory\WastageController as InventoryWastageController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\Intelligence\AnalyticsController;
use App\Http\Controllers\Intelligence\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TaxController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('pos', [PosController::class, 'index'])->name('pos.index');
    Route::get('pos/products', [PosController::class, 'products'])->name('pos.products');
    Route::get('pos/customers', [PosController::class, 'lookupCustomer'])->name('pos.customers');
    Route::post('pos/quote', [PosController::class, 'quote'])->name('pos.quote');
    Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}/receipt', [OrderController::class, 'receipt'])->name('orders.receipt');
    Route::get('recipes', [RecipeController::class, 'index'])->name('recipes.index');

    Route::middleware('admin')->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::post('categories/{category}/toggle', [CategoryController::class, 'toggle'])->name('categories.toggle');
        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('menu-items', MenuItemController::class)->except('show');
        Route::redirect('condiments', '/add-ons');
        Route::resource('add-ons', CondimentController::class)
            ->parameters(['add-ons' => 'condiment'])
            ->except(['show', 'create', 'edit'])
            ->names([
                'index' => 'condiments.index',
                'store' => 'condiments.store',
                'update' => 'condiments.update',
                'destroy' => 'condiments.destroy',
            ]);
        Route::get('taxes', [TaxController::class, 'edit'])->name('taxes.edit');
        Route::put('taxes', [TaxController::class, 'update'])->name('taxes.update');
        Route::put('taxes/rules/{taxRule}', [TaxController::class, 'updateRule'])->name('taxes.rules.update');
        Route::post('taxes/rules/{taxRule}/default', [TaxController::class, 'makeDefault'])->name('taxes.rules.default');
        Route::get('taxes/reports', [TaxController::class, 'reports'])->name('taxes.reports');
        Route::get('taxes/audit', [TaxController::class, 'audit'])->name('taxes.audit');
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
        Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::post('orders/{order}/refund', [OrderController::class, 'refund'])->name('orders.refund');

        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('/', InventoryDashboardController::class)->name('dashboard');
            Route::get('items/export', [InventoryItemController::class, 'export'])->name('items.export');
            Route::get('items/import', [InventoryItemController::class, 'importForm'])->name('items.import');
            Route::post('items/import', [InventoryItemController::class, 'import'])->name('items.import.store');
            Route::resource('items', InventoryItemController::class)->except('show');
            Route::resource('categories', InventoryCategoryController::class)->except(['create', 'edit', 'show']);
            Route::get('stock', [InventoryStockController::class, 'index'])->name('stock.index');
            Route::get('ledger', [InventoryStockController::class, 'ledger'])->name('ledger');
            Route::get('ledger/export', [InventoryStockController::class, 'exportLedger'])->name('ledger.export');
            Route::get('low-stock', [InventoryStockController::class, 'low'])->name('low');
            Route::get('expiry', [InventoryStockController::class, 'expiry'])->name('expiry');
            Route::resource('purchases', InventoryPurchaseController::class)->only(['index', 'create', 'store', 'show']);
            Route::resource('suppliers', InventorySupplierController::class)->except('show');
            Route::post('recipes/{recipe}/produce', [InventoryRecipeController::class, 'produce'])->name('recipes.produce');
            Route::resource('recipes', InventoryRecipeController::class)->except('show');
            Route::get('adjustments', [InventoryAdjustmentController::class, 'index'])->name('adjustments.index');
            Route::post('adjustments', [InventoryAdjustmentController::class, 'store'])->name('adjustments.store');
            Route::get('wastage', [InventoryWastageController::class, 'index'])->name('wastage.index');
            Route::post('wastage', [InventoryWastageController::class, 'store'])->name('wastage.store');
            Route::get('transfers', [InventoryTransferController::class, 'index'])->name('transfers.index');
            Route::post('transfers', [InventoryTransferController::class, 'store'])->name('transfers.store');
            Route::resource('stocktakes', InventoryStocktakeController::class)->only(['index', 'store', 'show', 'update']);
            Route::post('stocktakes/{stocktake}/confirm', [InventoryStocktakeController::class, 'confirm'])->name('stocktakes.confirm');
            Route::get('reports', [InventoryReportController::class, 'index'])->name('reports');
            Route::get('reports/valuation.csv', [InventoryReportController::class, 'exportValuation'])->name('reports.valuation');
            Route::get('units', [CatalogExtrasController::class, 'units'])->name('units.index');
            Route::post('units', [CatalogExtrasController::class, 'storeUnit'])->name('units.store');
            Route::get('locations', [CatalogExtrasController::class, 'locations'])->name('locations.index');
            Route::post('locations', [CatalogExtrasController::class, 'storeLocation'])->name('locations.store');
        });
    });
});
