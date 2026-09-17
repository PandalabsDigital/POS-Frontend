<a class="module-card" href="{{ route($route, $params ?? [], false) }}">
    <span class="module-icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
    <span>{{ $label }}</span>
    <small>{{ $hint }}</small>
</a>
