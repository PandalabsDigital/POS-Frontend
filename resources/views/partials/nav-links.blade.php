<nav class="d-grid gap-1">
    @if(auth()->user()->isAdmin())
        @include('partials.nav-link', ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-house-door-fill'])
        @include('partials.nav-link', ['route' => 'categories.index', 'match' => 'categories.*', 'label' => 'Categories', 'icon' => 'bi-grid-fill'])
        @include('partials.nav-link', ['route' => 'menu-items.index', 'match' => 'menu-items.*', 'label' => 'Menu', 'icon' => 'bi-fork-knife'])
        @include('partials.nav-link', ['route' => 'condiments.index', 'match' => 'condiments.*', 'label' => 'Add Ons', 'icon' => 'bi-plus-circle-fill'])
        @include('partials.nav-link', ['route' => 'taxes.edit', 'match' => 'taxes.*', 'label' => 'Taxation', 'icon' => 'bi-percent'])
        @include('partials.nav-link', ['route' => 'inventory.dashboard', 'match' => 'inventory.*', 'label' => 'Inventory', 'icon' => 'bi-box-seam-fill'])
        @include('partials.nav-link', ['route' => 'reports.index', 'match' => 'reports.*', 'label' => 'Reports', 'icon' => 'bi-bar-chart-fill'])
        @include('partials.nav-link', ['route' => 'analytics.index', 'match' => 'analytics.*', 'label' => 'Analytics', 'icon' => 'bi-lightbulb-fill'])
        @include('partials.nav-link', ['route' => 'customers.index', 'match' => 'customers.*', 'label' => 'Customers', 'icon' => 'bi-people-fill'])
        @include('partials.nav-link', ['route' => 'settings.edit', 'match' => 'settings.*', 'label' => 'Settings', 'icon' => 'bi-gear-fill'])
    @endif
    @include('partials.nav-link', ['route' => 'recipes.index', 'match' => 'recipes.*', 'label' => 'Recipes', 'icon' => 'bi-youtube'])
    @include('partials.nav-link', ['route' => 'pos.index', 'match' => 'pos.*', 'label' => 'POS Screen', 'icon' => 'bi-tablet'])
</nav>
