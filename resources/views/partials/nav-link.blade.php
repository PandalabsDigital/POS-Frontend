@php
    $href = route($route, [], false);
@endphp
<a href="{{ $href }}" class="{{ request()->routeIs($match) ? 'active' : '' }}">
    @if(! empty($icon))
        <i class="bi {{ $icon }}" aria-hidden="true"></i>
    @endif
    <span>{{ $label }}</span>
</a>
