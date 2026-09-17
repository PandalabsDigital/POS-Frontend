<?php

namespace Database\Seeders;

use App\Models\TaxRule;
use App\Models\TaxSetting;
use App\Taxation\TaxRuleCatalog;
use Illuminate\Database\Seeder;

class TaxRuleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TaxRuleCatalog::rules() as $preset) {
            $components = $preset['components'];
            unset($preset['components']);

            $rule = TaxRule::query()->firstOrCreate(
                ['code' => $preset['code']],
                $preset,
            );

            if ($rule->wasRecentlyCreated) {
                foreach ($components as $index => $component) {
                    $rule->components()->create([
                        'name' => $component['name'],
                        'rate' => $component['rate'],
                        'sort_order' => $index,
                    ]);
                }
            }
        }

        $default = TaxRule::query()->where('code', 'in_gst_restaurant_5')->first();

        if (! TaxSetting::query()->exists()) {
            TaxSetting::query()->create([
                'country_code' => 'IN',
                'tax_registered' => true,
                'tax_authority' => config('taxation.countries.IN.authority'),
                'tax_invoice_enabled' => true,
                'prices_include_tax' => false,
                'default_tax_rule_id' => $default?->id,
                'supply_type' => 'intra',
                'service_charge_enabled' => false,
                'service_charge_rate' => 0,
                'service_charge_taxable' => false,
            ]);
        }
    }
}
