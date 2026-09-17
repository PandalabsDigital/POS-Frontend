@php
    $percent = $delta['percent'] ?? null;
    $amount = $delta['amount'] ?? 0;
    $class = $amount > 0 ? 'text-success' : ($amount < 0 ? 'text-danger' : 'text-muted');
@endphp
<span class="{{ $class }} small">
    {{ $amount >= 0 ? '+' : '' }}@money($amount)
    @if($percent === null)
        <span class="text-muted">(no prior)</span>
    @else
        ({{ $percent >= 0 ? '+' : '' }}{{ $percent }}%)
    @endif
</span>
