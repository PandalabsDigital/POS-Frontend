@extends('layouts.app')

@section('title', 'Taxation')

@section('content')
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Taxation</h1>
        <p class="text-muted mb-0">Configure tax rules, rates and invoice taxation for your restaurant.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('taxes.reports') }}">Tax reports</a>
        <a class="btn btn-outline-secondary" href="{{ route('taxes.audit') }}">Audit log</a>
    </div>
</div>

<form method="POST" action="{{ route('taxes.update') }}" class="stat-card mb-4" id="tax-settings-form">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Country</label>
            <select name="country_code" id="tax-country" class="form-select" required>
                @foreach($countries as $code => $country)
                    <option value="{{ $code }}"
                        data-currency="{{ $country['currency'] }}"
                        data-authority="{{ $country['authority'] }}"
                        @selected(old('country_code', $settings->country_code) === $code)>
                        {{ $country['name'] }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Currency</label>
            <select name="currency_code" id="tax-currency" class="form-select" required>
                @foreach($currencies as $code => $item)
                    <option value="{{ $code }}" @selected(old('currency_code', $currencyCode) === $code)>{{ $item['name'] }} ({{ $code }})</option>
                @endforeach
            </select>
            <div class="form-text">Suggested from the selected country. You can still change it.</div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Tax authority</label>
            <input name="tax_authority" id="tax-authority" class="form-control" value="{{ old('tax_authority', $settings->tax_authority) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Tax registered</label>
            <select name="tax_registered" class="form-select">
                <option value="1" @selected(old('tax_registered', $settings->tax_registered))>Yes</option>
                <option value="0" @selected(! old('tax_registered', $settings->tax_registered))>No</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Tax registration number</label>
            <input name="tax_registration_number" class="form-control" value="{{ old('tax_registration_number', $settings->tax_registration_number) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Tax invoice enabled</label>
            <select name="tax_invoice_enabled" class="form-select">
                <option value="1" @selected(old('tax_invoice_enabled', $settings->tax_invoice_enabled))>Yes</option>
                <option value="0" @selected(! old('tax_invoice_enabled', $settings->tax_invoice_enabled))>No</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Prices include tax</label>
            <select name="prices_include_tax" class="form-select">
                <option value="0" @selected(! old('prices_include_tax', $settings->prices_include_tax))>No — tax exclusive</option>
                <option value="1" @selected(old('prices_include_tax', $settings->prices_include_tax))>Yes — tax inclusive</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Place of supply (India GST)</label>
            <select name="supply_type" class="form-select">
                <option value="intra" @selected(old('supply_type', $settings->supply_type) === 'intra')>Intra-state (CGST + SGST)</option>
                <option value="inter" @selected(old('supply_type', $settings->supply_type) === 'inter')>Inter-state (IGST)</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Default tax rule</label>
            <select name="default_tax_rule_id" class="form-select" required>
                @foreach($rules as $rule)
                    <option value="{{ $rule->id }}" @selected((int) old('default_tax_rule_id', $settings->default_tax_rule_id) === $rule->id)>
                        {{ $rule->tax_name }} · {{ $rule->tax_type }} · {{ $rule->formatRate() }}%
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Service charge</label>
            <select name="service_charge_enabled" class="form-select">
                <option value="0" @selected(! old('service_charge_enabled', $settings->service_charge_enabled))>Disabled</option>
                <option value="1" @selected(old('service_charge_enabled', $settings->service_charge_enabled))>Enabled</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Service charge rate (%)</label>
            <input type="number" step="0.001" min="0" max="100" name="service_charge_rate" class="form-control" value="{{ old('service_charge_rate', $settings->service_charge_rate) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Service charge taxable</label>
            <select name="service_charge_taxable" class="form-select">
                <option value="0" @selected(! old('service_charge_taxable', $settings->service_charge_taxable))>No</option>
                <option value="1" @selected(old('service_charge_taxable', $settings->service_charge_taxable))>Yes</option>
            </select>
        </div>
    </div>
    <p class="small text-muted mt-3 mb-3">Changing rates never rewrites past invoices. Each receipt stores the tax snapshot used at the time of sale.</p>
    <button class="btn btn-accent">Save taxation settings</button>
</form>

<div class="stat-card">
    <h2 class="h5 mb-3">Tax rules</h2>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Tax Name</th>
                    <th>Tax Type</th>
                    <th>Rate</th>
                    <th>Components</th>
                    <th>ITC</th>
                    <th>Status</th>
                    <th>Default</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($rules as $rule)
                <tr>
                    <td>{{ $rule->tax_name }}</td>
                    <td>{{ $rule->tax_type }}</td>
                    <td>{{ $rule->formatRate() }}%</td>
                    <td>{{ $rule->componentsLabel() }}</td>
                    <td>{{ $rule->itcLabel() }}</td>
                    <td>{{ $rule->is_active ? 'Active' : 'Inactive' }}</td>
                    <td>{{ $rule->is_default ? 'Yes' : 'No' }}</td>
                    <td class="text-nowrap">
                        @unless($rule->is_default)
                            <form method="POST" action="{{ route('taxes.rules.default', $rule) }}" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary">Set default</button>
                            </form>
                        @endunless
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#rule-{{ $rule->id }}">Edit rate</button>
                    </td>
                </tr>
                <tr class="collapse" id="rule-{{ $rule->id }}">
                    <td colspan="8">
                        <form method="POST" action="{{ route('taxes.rules.update', $rule) }}" class="row g-2 align-items-end">
                            @csrf @method('PUT')
                            <div class="col-md-2">
                                <label class="form-label">Rate %</label>
                                <input type="number" step="0.001" min="0" max="100" name="rate" class="form-control" value="{{ $rule->rate }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select name="is_active" class="form-select">
                                    <option value="1" @selected($rule->is_active)>Active</option>
                                    <option value="0" @selected(! $rule->is_active)>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Reason</label>
                                <input name="reason" class="form-control" required placeholder="Why is this rate changing?">
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-accent">Save rule</button>
                            </div>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/taxation.js') }}"></script>
@endpush
