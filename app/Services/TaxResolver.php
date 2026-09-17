<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\TaxRule;
use App\Models\TaxSetting;

class TaxResolver
{
    public function resolve(?MenuItem $item, ?TaxSetting $settings = null): TaxRule
    {
        $settings ??= TaxSetting::current();
        $rule = null;

        if ($item?->relationLoaded('taxRule') && $item->taxRule) {
            $rule = $item->taxRule;
        } elseif ($item?->tax_rule_id) {
            $rule = TaxRule::query()->with('components')->find($item->tax_rule_id);
        }

        if (! $rule && $item) {
            $item->loadMissing('category.taxRule.components');
            $rule = $item->category?->taxRule;
        }

        if (! $rule && $settings->default_tax_rule_id) {
            $rule = $settings->defaultRule ?: TaxRule::query()->with('components')->find($settings->default_tax_rule_id);
        }

        if (! $rule) {
            $rule = TaxRule::query()
                ->with('components')
                ->where('country_code', $settings->country_code)
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();
        }

        if (! $rule) {
            $rule = TaxRule::query()
                ->with('components')
                ->where('country_code', $settings->country_code)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->firstOrFail();
        }

        $rule->loadMissing('components');

        return $this->matchSupply($rule, $settings);
    }

    public function matchSupply(TaxRule $rule, TaxSetting $settings): TaxRule
    {
        $supply = $settings->supply_type ?: 'intra';

        if ($rule->supply_scope === 'any' || $rule->supply_scope === $supply || ! $rule->family) {
            return $rule;
        }

        $match = TaxRule::query()
            ->with('components')
            ->where('country_code', $rule->country_code)
            ->where('family', $rule->family)
            ->where('is_active', true)
            ->where(function ($query) use ($supply) {
                $query->where('supply_scope', $supply)->orWhere('supply_scope', 'any');
            })
            ->orderByRaw('CASE WHEN supply_scope = ? THEN 0 ELSE 1 END', [$supply])
            ->first();

        return $match ?: $rule;
    }
}
