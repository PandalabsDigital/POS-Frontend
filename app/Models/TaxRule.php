<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'country_code',
    'tax_name',
    'tax_type',
    'classification',
    'family',
    'supply_scope',
    'rate',
    'itc',
    'is_default',
    'is_active',
])]
class TaxRule extends Model
{
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:3',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function components(): HasMany
    {
        return $this->hasMany(TaxRuleComponent::class)->orderBy('sort_order');
    }

    public function isNotApplicable(): bool
    {
        return $this->classification === 'not_applicable';
    }

    public function itcLabel(): string
    {
        return match ($this->itc) {
            'available' => 'Yes',
            'none' => 'No',
            default => 'N/A',
        };
    }

    public function componentsLabel(): string
    {
        $parts = $this->components->map(fn (TaxRuleComponent $component) => $component->name.' '.$this->formatRate((float) $component->rate).'%');

        return $parts->isEmpty() ? '—' : $parts->join(' + ');
    }

    public function classificationLabel(): string
    {
        return config('taxation.classifications.'.$this->classification, $this->tax_type);
    }

    public function formatRate(?float $rate = null): string
    {
        $value = $rate ?? (float) $this->rate;

        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }
}
