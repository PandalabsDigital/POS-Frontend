<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaxSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $countries = array_keys(config('taxation.countries'));
        $currencies = array_keys(config('currencies'));

        return [
            'country_code' => ['required', 'string', Rule::in($countries)],
            'currency_code' => ['required', 'string', Rule::in($currencies)],
            'tax_registered' => ['required', 'boolean'],
            'tax_registration_number' => ['nullable', 'string', 'max:80'],
            'tax_authority' => ['nullable', 'string', 'max:120'],
            'tax_invoice_enabled' => ['required', 'boolean'],
            'prices_include_tax' => ['required', 'boolean'],
            'default_tax_rule_id' => ['required', 'integer', 'exists:tax_rules,id'],
            'supply_type' => ['required', Rule::in(['intra', 'inter'])],
            'service_charge_enabled' => ['required', 'boolean'],
            'service_charge_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'service_charge_taxable' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tax_registered' => $this->boolean('tax_registered'),
            'tax_invoice_enabled' => $this->boolean('tax_invoice_enabled'),
            'prices_include_tax' => $this->boolean('prices_include_tax'),
            'service_charge_enabled' => $this->boolean('service_charge_enabled'),
            'service_charge_taxable' => $this->boolean('service_charge_taxable'),
        ]);
    }
}
