@php
    $links = $reports ?? \App\Services\Intelligence\ReportCatalog::reports();
    $query = isset($filter) ? $filter->query() : [];
    $onReports = request()->routeIs('reports.*');
    $onAnalytics = request()->routeIs('analytics.*');
@endphp
<div class="intel-switch no-print mb-3">
    <a class="{{ $onReports ? 'active' : '' }}" href="{{ route('reports.index') }}">
        <i class="bi bi-clipboard-data" aria-hidden="true"></i>
        Reports
    </a>
    <a class="{{ $onAnalytics ? 'active' : '' }}" href="{{ isset($filter) ? route('analytics.index', $filter->query()) : route('analytics.index') }}">
        <i class="bi bi-lightbulb-fill" aria-hidden="true"></i>
        Analytics
    </a>
</div>
@if($onReports && ! request()->routeIs('reports.index'))
<div class="intel-icon-nav no-print mb-4">
    <a class="intel-icon-link {{ request()->routeIs('reports.index') ? 'active' : '' }}" href="{{ route('reports.index') }}">
        <span class="intel-icon-bubble"><i class="bi bi-house-door-fill" aria-hidden="true"></i></span>
        <span>Home</span>
    </a>
    @foreach($links as $slug => $item)
        <a class="intel-icon-link {{ request()->routeIs('reports.show') && request()->route('report') === $slug ? 'active' : '' }}" href="{{ route('reports.show', array_merge(['report' => $slug], $query)) }}">
            <span class="intel-icon-bubble"><i class="bi {{ $item['icon'] }}" aria-hidden="true"></i></span>
            <span>{{ $item['title'] }}</span>
        </a>
    @endforeach
</div>
@endif
