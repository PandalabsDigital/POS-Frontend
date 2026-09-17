<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="icon" href="{{ asset('images/logo-icon.jpg') }}">
    <title>Receipt {{ $order->invoice_number }}</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body style="background:#eee;">
<div class="no-print text-center py-3">
    <button onclick="window.print()">Print receipt</button>
</div>
<div class="receipt">
    <div style="text-align:center;margin-bottom:12px">
        @if(! empty($restaurantLogoUrl))
            <img src="{{ $restaurantLogoUrl }}" alt="{{ $restaurantName }}" class="receipt-restaurant-logo">
        @endif
        <div><strong>{{ $restaurantName }}</strong></div>
        @if(! empty($restaurantAddress))
            <div>{{ $restaurantAddress }}</div>
        @endif
        @if(! empty($restaurantPhone))
            <div>{{ $restaurantPhone }}</div>
        @endif
    </div>
    --------------------------------
    <div>Invoice: {{ $order->invoice_number }}</div>
    <div>Date: {{ $order->completed_at?->format('Y-m-d H:i') }}</div>
    @if($showCashierOnReceipt ?? true)
        <div>Cashier: {{ $order->cashier?->name ?? '—' }}</div>
    @endif
    <div>Type: {{ $order->type->label() }}</div>
    @if($order->customer_capture_status?->value === 'captured' && ($order->customer_name || $order->customer_phone))
        <div>Customer: {{ $order->customer_name }}</div>
        @if($order->customer_phone)
            <div>Phone: {{ $order->customer_phone }}</div>
        @endif
    @endif
    @if($order->type->value === 'delivery' && filled($order->delivery_address))
        --------------------------------
        <div><strong>Deliver to</strong></div>
        <div style="white-space:pre-wrap">{{ $order->delivery_address }}</div>
    @endif
    --------------------------------
    @foreach($order->items as $item)
        <div style="margin:8px 0">
            <div>{{ $item->quantity }} x {{ $item->name }}</div>
            @if($item->size_name)<div>Size: {{ $item->size_name }}</div>@endif
            @if($item->condiments)
                <div>Add-ons: {{ collect($item->condiments)->pluck('name')->join(', ') }}</div>
            @endif
            @if($item->notes)<div>Note: {{ $item->notes }}</div>@endif
            <div style="text-align:right">@money($item->line_total)</div>
        </div>
    @endforeach
    --------------------------------
    <div>Subtotal: @money($order->subtotal)</div>
    @if((float) $order->discount_amount > 0)
        <div>Discount: {{ rtrim(rtrim(number_format($order->discountRate(), 2, '.', ''), '0'), '.') }}%</div>
    @endif
    @if((float) $order->service_charge_amount > 0)
        <div>Service Charge: @money($order->service_charge_amount)</div>
    @endif
    @php
        $snapshot = $order->tax_snapshot ?? [];
    @endphp
    @if(($snapshot['display'] ?? '') === 'not_applicable' || ! $order->tax_applicable)
        <div>Tax: Not Applicable</div>
    @else
        @if((float) $order->taxable_amount > 0)
            <div>Taxable Value: @money($order->taxable_amount)</div>
        @endif
        @forelse(($snapshot['breakdown'] ?? []) as $row)
            <div>{{ $row['name'] }}: @money($row['amount'])</div>
        @empty
            <div>{{ $order->tax_label ?: 'Tax' }}: @money($order->tax_amount)</div>
        @endforelse
        <div>Total Tax: @money($order->tax_amount)</div>
    @endif
    <div><strong>Grand Total: @money($order->grand_total)</strong></div>
    @if(! empty($snapshot['tax_invoice_enabled']) && ! empty($snapshot['tax_registration_number']))
        <div>{{ $snapshot['tax_authority'] ?? 'Tax ID' }}: {{ $snapshot['tax_registration_number'] }}</div>
    @endif
    @if($order->payment_method)
        <div>Payment: {{ config('taxation.payment_methods.'.$order->payment_method, $order->payment_method) }}</div>
    @endif
    --------------------------------
    <div class="receipt-thanks">{{ $receiptFooter ?? 'Thank you' }}</div>
    <div class="receipt-powered">
        <span>Powered by</span>
        <img src="{{ $restAssuredLogoUrl ?? asset('images/logo-icon.jpg') }}" alt="RestAssured">
    </div>
</div>
</body>
</html>
