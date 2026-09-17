@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
    <div class="d-flex align-items-center gap-3 min-w-0">
        @include('partials.restaurant-logo')
        <div class="min-w-0">
            <h1 class="h3 mb-1">{{ $restaurantName }}</h1>
            <p class="text-muted mb-0">Restaurant Management System POS</p>
        </div>
    </div>
    <a href="{{ route('pos.index', [], false) }}" class="btn btn-accent">
        <i class="bi bi-tablet" aria-hidden="true"></i>
        Open POS
    </a>
</div>

<div class="dashboard-layout">
    <section class="dashboard-modules mb-4">
        <div class="dashboard-section-kicker">Modules</div>
        <h2 class="h5 mb-3">Workspaces</h2>
        <div class="row g-3">
            <div class="col-6 col-md-4 col-xl">
                @include('partials.module-card', ['route' => 'pos.index', 'icon' => 'bi-tablet', 'label' => 'POS Screen', 'hint' => 'Take orders'])
            </div>
            <div class="col-6 col-md-4 col-xl">
                @include('partials.module-card', ['route' => 'categories.index', 'icon' => 'bi-grid-fill', 'label' => 'Categories', 'hint' => 'Burgers, pizza, drinks'])
            </div>
            <div class="col-6 col-md-4 col-xl">
                @include('partials.module-card', ['route' => 'menu-items.index', 'icon' => 'bi-fork-knife', 'label' => 'Menu', 'hint' => 'Items, prices, photos'])
            </div>
            <div class="col-6 col-md-4 col-xl">
                @include('partials.module-card', ['route' => 'condiments.index', 'icon' => 'bi-plus-circle-fill', 'label' => 'Add Ons', 'hint' => 'Cheese, sauce, fries'])
            </div>
            <div class="col-6 col-md-4 col-xl">
                @include('partials.module-card', ['route' => 'taxes.edit', 'icon' => 'bi-percent', 'label' => 'Taxation', 'hint' => 'GST, VAT, invoices'])
            </div>
            <div class="col-6 col-md-4 col-xl">
                @include('partials.module-card', ['route' => 'inventory.dashboard', 'icon' => 'bi-box-seam-fill', 'label' => 'Inventory', 'hint' => 'Stock, recipes, waste'])
            </div>
            <div class="col-6 col-md-4 col-xl">
                @include('partials.module-card', ['route' => 'reports.index', 'icon' => 'bi-bar-chart-fill', 'label' => 'Reports', 'hint' => 'What happened'])
            </div>
            <div class="col-6 col-md-4 col-xl">
                @include('partials.module-card', ['route' => 'analytics.index', 'icon' => 'bi-lightbulb-fill', 'label' => 'Analytics', 'hint' => 'What to do next'])
            </div>
            <div class="col-6 col-md-4 col-xl">
                @include('partials.module-card', ['route' => 'customers.index', 'icon' => 'bi-people-fill', 'label' => 'Customers', 'hint' => 'Names and phones'])
            </div>
            <div class="col-6 col-md-4 col-xl">
                @include('partials.module-card', ['route' => 'recipes.index', 'icon' => 'bi-youtube', 'label' => 'Recipes', 'hint' => 'Cuisines on YouTube'])
            </div>
            <div class="col-6 col-md-4 col-xl">
                @include('partials.module-card', ['route' => 'settings.edit', 'icon' => 'bi-gear-fill', 'label' => 'Settings', 'hint' => 'Profile, receipts, POS'])
            </div>
        </div>
    </section>

    <section class="dashboard-analytics mb-4">
        <div class="dashboard-section-kicker">Analytics</div>
        <h2 class="h5 mb-3">Today and this week</h2>
        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-label"><i class="bi bi-cash-stack" aria-hidden="true"></i> Today's Revenue</div>
                    <div class="display-6">@money($todayRevenue)</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-label"><i class="bi bi-receipt" aria-hidden="true"></i> Today's Orders</div>
                    <div class="display-6">{{ $todayOrders }}</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-label"><i class="bi bi-box-seam" aria-hidden="true"></i> Total Products</div>
                    <div class="display-6">{{ $totalProducts }}</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-label"><i class="bi bi-grid-fill" aria-hidden="true"></i> Total Categories</div>
                    <div class="display-6">{{ $totalCategories }}</div>
                </div>
            </div>
        </div>
        <div>
            <h3 class="h6 mb-3">Revenue (last 7 days)</h3>
            <canvas id="revenueChart" height="90"></canvas>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [{
                label: 'Revenue',
                data: @json($chartValues),
                borderColor: '#ff6a00',
                backgroundColor: 'rgba(255, 106, 0, 0.16)',
                fill: true,
                tension: 0.35,
            }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
</script>
@endpush
