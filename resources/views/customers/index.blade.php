@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Customers</h1>
        <p class="text-muted mb-0">Names and phone numbers captured at checkout.</p>
    </div>
    <form class="d-flex gap-2">
        <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Search name or phone" inputmode="search">
        <button class="btn btn-accent">Search</button>
    </form>
</div>
<div class="stat-card">
    <div class="table-responsive">
    <table class="table align-middle">
        <thead><tr><th>Name</th><th>Phone</th><th>Orders</th><th>Last order</th></tr></thead>
        <tbody>
        @forelse($customers as $customer)
            <tr>
                <td class="fw-semibold">{{ $customer->displayName() }}</td>
                <td>{{ $customer->phone }}</td>
                <td>{{ $customer->orders_count }}</td>
                <td>{{ $customer->last_ordered_at?->format('Y-m-d H:i') ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-muted">No customers yet. Complete a POS order with name and phone.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    {{ $customers->links() }}
</div>
@endsection
