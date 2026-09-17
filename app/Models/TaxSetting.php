<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'country_code',
    'tax_registered',
    'tax_registration_number',
    'tax_authority',
    'tax_invoice_enabled',
    'prices_include_tax',
    'default_tax_rule_id',
    'supply_type',
    'service_charge_enabled',
    'service_charge_rate',
    'service_charge_taxable',
])]
class TaxSetting extends Model
{
    protected function casts(): array
    {
        return [
            'tax_registered' => 'boolean',
            'tax_invoice_enabled' => 'boolean',
            'prices_include_tax' => 'boolean',
            'service_charge_enabled' => 'boolean',
            'service_charge_taxable' => 'boolean',
            'service_charge_rate' => 'decimal:3',
        ];
    }

    public function defaultRule(): BelongsTo
    {
        return $this->belongsTo(TaxRule::class, 'default_tax_rule_id');
    }

    public static function current(): self
    {
        $settings = static::query()->with('defaultRule.components')->first();

        if ($settings) {
            return $settings;
        }

        return static::query()->create([
            'country_code' => 'IN',
            'tax_registered' => true,
            'tax_authority' => config('taxation.countries.IN.authority'),
            'tax_invoice_enabled' => true,
            'prices_include_tax' => false,
            'supply_type' => 'intra',
        ]);
    }

    public function countryName(): string
    {
        return (string) config('taxation.countries.'.$this->country_code.'.name', $this->country_code);
    }
}
