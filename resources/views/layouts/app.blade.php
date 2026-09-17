<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ff6a00">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="icon" href="{{ asset('images/logo-icon.jpg') }}">
    <title>@yield('title', $restaurantName)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body class="app-body">
<header class="mobile-topbar d-lg-none">
    <button class="btn btn-accent" type="button" data-bs-toggle="offcanvas" data-bs-target="#appNav" aria-label="Open modules">
        <i class="bi bi-list" aria-hidden="true"></i>
        <span>Modules</span>
    </button>
    <div class="d-flex align-items-center gap-2 min-w-0">
        @include('partials.restaurant-logo', ['class' => 'mobile-restaurant-logo'])
        <strong class="text-truncate">{{ $restaurantName }}</strong>
    </div>
    <a href="{{ route('pos.index', [], false) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-tablet" aria-hidden="true"></i>
        <span>POS</span>
    </a>
</header>

<div class="offcanvas offcanvas-start" tabindex="-1" id="appNav">
    <div class="offcanvas-header">
        <div class="sidebar-brand">
            @include('partials.restassured-lockup')
            <div class="small text-muted">{{ auth()->user()->role->label() }} · {{ $currency['code'] }}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        @include('partials.nav-links')
        <form method="POST" action="{{ route('logout', [], false) }}" class="mt-auto">
            @csrf
            <button class="btn btn-outline-secondary w-100">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                Log out
            </button>
        </form>
    </div>
</div>

<div class="d-flex app-shell">
    <aside class="sidebar p-3 d-none d-lg-flex flex-column">
        <div class="sidebar-brand mb-4">
            @include('partials.restassured-lockup')
            <div class="small text-muted">{{ auth()->user()->role->label() }} · {{ $currency['code'] }}</div>
        </div>
        @include('partials.nav-links')
        <form method="POST" action="{{ route('logout', [], false) }}" class="mt-auto">
            @csrf
            <button class="btn btn-outline-secondary w-100">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                Log out
            </button>
        </form>
    </aside>
    <main class="flex-grow-1 p-3 p-md-4 p-lg-5 app-main">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        @yield('content')
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
