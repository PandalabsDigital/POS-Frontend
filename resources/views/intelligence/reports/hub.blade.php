@extends('layouts.app')

@section('title', 'Reports')

@section('content')
@include('intelligence.reports._nav')
<div class="intel-hero mb-4">
    <span class="intel-page-icon" aria-hidden="true"><i class="bi bi-clipboard-data"></i></span>
    <div>
        <h1 class="h3 mb-1">Reports</h1>
        <p class="text-muted mb-0">Pick a picture. See what happened.</p>
    </div>
</div>
@foreach(\App\Services\Intelligence\ReportCatalog::groups() as $group => $label)
    @php $items = collect($reports)->filter(fn ($item) => $item['group'] === $group); @endphp
    @if($items->isNotEmpty())
        <h2 class="h6 text-muted mb-2">{{ $label }}</h2>
        <div class="row g-3 mb-4">
            @foreach($items as $slug => $item)
                <div class="col-4 col-md-3 col-xl-2">
                    @include('partials.module-card', [
                        'route' => 'reports.show',
                        'icon' => $item['icon'],
                        'label' => $item['title'],
                        'hint' => $item['hint'],
                        'params' => ['report' => $slug],
                    ])
                </div>
            @endforeach
        </div>
    @endif
@endforeach
@endsection
