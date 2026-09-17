@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
<div class="login-wrap d-flex align-items-center justify-content-center p-4">
    <div class="login-card shadow">
        <div class="text-center mb-4">
            <img src="{{ $restAssuredLogoUrl ?? asset('images/logo-icon.jpg') }}" alt="RestAssured" class="brand-icon brand-icon-xl mx-auto">
            <div class="fw-bold mt-3">{{ $restaurantName }}</div>
        </div>
        <h1 class="h4 mb-4">Welcome back</h1>
        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="{{ old('email', 'admin@pos.test') }}" class="form-control @error('email') is-invalid @enderror" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" value="password" required>
            </div>
            <label class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember">
                <span class="form-check-label">Remember me</span>
            </label>
            <button class="btn btn-accent w-100">Sign in</button>
        </form>
        <p class="small text-muted mt-4 mb-0">Admin: admin@pos.test / password<br>Cashier: cashier@pos.test / password</p>
    </div>
</div>
@endsection
