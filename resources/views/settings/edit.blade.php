@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<h1 class="h3 mb-2">Settings</h1>
<p class="text-muted mb-4">Restaurant profile, receipts and POS defaults.</p>
<form method="POST" action="{{ route('settings.update') }}" class="stat-card settings-card" enctype="multipart/form-data">
    @csrf @method('PUT')
    <h2 class="h6 mb-3">Restaurant</h2>
    <div class="mb-3">
        <label class="form-label">Restaurant name</label>
        <input name="restaurant_name" class="form-control" value="{{ old('restaurant_name', $settings['name']) }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Address</label>
        <input name="address" class="form-control" value="{{ old('address', $settings['address']) }}">
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label">City</label>
            <input name="city" class="form-control" value="{{ old('city', $settings['city']) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input name="phone" class="form-control" value="{{ old('phone', $settings['phone']) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $settings['email']) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Website</label>
            <input name="website" class="form-control" value="{{ old('website', $settings['website']) }}">
        </div>
    </div>
    <div class="mb-4">
        <label class="form-label">Restaurant logo</label>
        @if(! empty($restaurantLogoUrl))
            <div class="mb-2">
                <img src="{{ $restaurantLogoUrl }}" alt="{{ $settings['name'] }}" class="restaurant-logo-preview">
            </div>
            <label class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="remove_restaurant_logo" value="1">
                <span class="form-check-label">Remove current logo</span>
            </label>
        @endif
        <input type="file" name="restaurant_logo" class="form-control @error('restaurant_logo') is-invalid @enderror" accept="image/png,image/jpeg,image/webp,image/gif">
        @error('restaurant_logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">JPG, PNG, WEBP, or GIF up to 2 MB. Shown on the dashboard and at the top of receipts.</div>
    </div>
    <div class="mb-4">
        <label class="form-label">Opening hours</label>
        <input name="opening_hours" class="form-control" value="{{ old('opening_hours', $settings['opening_hours']) }}" placeholder="11:00 AM – 11:00 PM">
    </div>

    <h2 class="h6 mb-3">Display</h2>
    <div class="mb-3">
        <label class="form-label">Currency</label>
        <select name="currency_code" class="form-select" required>
            <optgroup label="Global">
                @foreach($currencies as $code => $item)
                    @if($item['group'] === 'global')
                        <option value="{{ $code }}" @selected(old('currency_code', $currencyCode) === $code)>{{ $item['name'] }} ({{ $code }})</option>
                    @endif
                @endforeach
            </optgroup>
            <optgroup label="GCC">
                @foreach($currencies as $code => $item)
                    @if($item['group'] === 'gcc')
                        <option value="{{ $code }}" @selected(old('currency_code', $currencyCode) === $code)>{{ $item['name'] }} ({{ $code }})</option>
                    @endif
                @endforeach
            </optgroup>
        </select>
        <div class="form-text">Prices stay the same numbers. This changes the symbol shown on POS, receipts, and reports.</div>
    </div>
    <div class="mb-4">
        <label class="form-label">Timezone</label>
        <select name="timezone" class="form-select" required>
            @foreach($timezones as $id => $label)
                <option value="{{ $id }}" @selected(old('timezone', $settings['timezone']) === $id)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <h2 class="h6 mb-3">POS & receipts</h2>
    <div class="mb-3">
        <label class="form-label">Default order type</label>
        <select name="default_order_type" class="form-select" required>
            @foreach($orderTypes as $type)
                <option value="{{ $type->value }}" @selected(old('default_order_type', $settings['default_order_type']) === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Invoice prefix</label>
        <input name="invoice_prefix" class="form-control" value="{{ old('invoice_prefix', $settings['invoice_prefix']) }}" maxlength="12">
        <div class="form-text">Printed as PREFIX-YYYYMMDD-0001.</div>
    </div>
    <div class="mb-3">
        <label class="form-label">Receipt footer</label>
        <input name="receipt_footer" class="form-control" value="{{ old('receipt_footer', $settings['receipt_footer']) }}">
    </div>
    <div class="mb-4">
        <label class="form-label">Show cashier on receipt</label>
        <select name="show_cashier_on_receipt" class="form-select">
            <option value="1" @selected(old('show_cashier_on_receipt', $settings['show_cashier_on_receipt']))>Yes</option>
            <option value="0" @selected(! old('show_cashier_on_receipt', $settings['show_cashier_on_receipt']))>No</option>
        </select>
    </div>
    <h2 class="h6 mb-3">Inventory</h2>
    <p class="small text-muted">POS deduction runs when an order is completed and paid. Recipe changes apply to future sales only.</p>
    @php $inv = $inventory; @endphp
    <div class="mb-3">
        <label class="form-label">Inventory tracking</label>
        <select name="inventory_enabled" class="form-select">
            <option value="1" @selected(old('inventory_enabled', $inv['enabled']))>On</option>
            <option value="0" @selected(! old('inventory_enabled', $inv['enabled']))>Off</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Automatic POS deduction</label>
        <select name="inventory_auto_deduct" class="form-select">
            <option value="1" @selected(old('inventory_auto_deduct', $inv['auto_deduct']))>On order completion / payment</option>
            <option value="0" @selected(! old('inventory_auto_deduct', $inv['auto_deduct']))>Manual</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Allow negative stock</label>
        <select name="inventory_allow_negative" class="form-select">
            <option value="0" @selected(! old('inventory_allow_negative', $inv['allow_negative']))>Prevent sale</option>
            <option value="1" @selected(old('inventory_allow_negative', $inv['allow_negative']))>Allow negative</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Auto-disable menu when ingredients unavailable</label>
        <select name="inventory_auto_disable_menu" class="form-select">
            <option value="0" @selected(! old('inventory_auto_disable_menu', $inv['auto_disable_menu']))>No</option>
            <option value="1" @selected(old('inventory_auto_disable_menu', $inv['auto_disable_menu']))>Yes</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Batch tracking</label>
        <select name="inventory_enable_batches" class="form-select">
            <option value="1" @selected(old('inventory_enable_batches', $inv['enable_batches']))>On</option>
            <option value="0" @selected(! old('inventory_enable_batches', $inv['enable_batches']))>Off</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Expiry tracking</label>
        <select name="inventory_enable_expiry" class="form-select">
            <option value="1" @selected(old('inventory_enable_expiry', $inv['enable_expiry']))>On</option>
            <option value="0" @selected(! old('inventory_enable_expiry', $inv['enable_expiry']))>Off</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Low stock notifications</label>
        <select name="inventory_low_stock_alerts" class="form-select">
            <option value="1" @selected(old('inventory_low_stock_alerts', $inv['low_stock_alerts']))>On</option>
            <option value="0" @selected(! old('inventory_low_stock_alerts', $inv['low_stock_alerts']))>Off</option>
        </select>
    </div>
    <div class="mb-4">
        <label class="form-label">Target food cost %</label>
        <input name="inventory_food_cost_target" type="number" step="0.1" class="form-control" value="{{ old('inventory_food_cost_target', $inv['food_cost_target']) }}">
        <div class="form-text">Costing method: weighted average.</div>
    </div>
    <button class="btn btn-accent w-100 w-md-auto">Save</button>
</form>
@endsection
