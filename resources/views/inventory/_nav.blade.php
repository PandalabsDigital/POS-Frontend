@php
    $links = [
        ['inventory.dashboard', 'Dashboard'],
        ['inventory.items.index', 'Items'],
        ['inventory.categories.index', 'Categories'],
        ['inventory.stock.index', 'Stock'],
        ['inventory.purchases.index', 'Purchases'],
        ['inventory.suppliers.index', 'Suppliers'],
        ['inventory.recipes.index', 'Recipes'],
        ['inventory.adjustments.index', 'Adjustments'],
        ['inventory.wastage.index', 'Wastage'],
        ['inventory.transfers.index', 'Transfers'],
        ['inventory.stocktakes.index', 'Stocktake'],
        ['inventory.low', 'Low stock'],
        ['inventory.expiry', 'Expiry'],
        ['inventory.reports', 'Reports'],
    ];
@endphp
<div class="d-flex flex-wrap gap-2 mb-4">
    @foreach($links as [$route, $label])
        <a class="btn btn-sm {{ request()->routeIs($route) || request()->routeIs(str_replace('.index', '.*', $route)) ? 'btn-accent' : 'btn-outline-secondary' }}" href="{{ route($route) }}">{{ $label }}</a>
    @endforeach
</div>
