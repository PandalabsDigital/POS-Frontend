@extends('layouts.app')

@section('title', 'Tax audit log')

@section('content')
<h1 class="h3 mb-2">Tax audit log</h1>
<p class="text-muted mb-4">Every tax configuration change is recorded. Invoice history is never silently rewritten.</p>
<div class="stat-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Date</th>
                    <th>Country</th>
                    <th>Action</th>
                    <th>Previous</th>
                    <th>New</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->user?->name ?? 'System' }}</td>
                    <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $log->country_code }}</td>
                    <td>{{ str_replace('_', ' ', $log->action) }}</td>
                    <td class="small"><pre class="mb-0">{{ json_encode($log->before, JSON_PRETTY_PRINT) }}</pre></td>
                    <td class="small"><pre class="mb-0">{{ json_encode($log->after, JSON_PRETTY_PRINT) }}</pre></td>
                    <td>{{ $log->reason }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-muted">No tax configuration changes yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $logs->links() }}
</div>
@endsection
