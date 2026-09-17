<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\Setting;
use App\Models\TaxRule;
use App\Models\TaxSetting;

class TaxEngine
{
    public function __construct(private TaxResolver $resolver) {}

    /**
     * @param  array<int, array{menu_item_id?: int, line_total: float, menu_item?: MenuItem|null}>  $lines
     * @return array<string, mixed>
     */
    public function calculateTax(array $lines, float $discount = 0, ?string $orderType = null, ?string $customerType = null): array
    {
        $settings = TaxSetting::current();
        $decimals = (int) Setting::currency()['decimals'];
        $subtotal = round(array_sum(array_map(fn (array $line) => (float) $line['line_total'], $lines)), $decimals);
        $discount = min(max(0, round($discount, $decimals)), $subtotal);

        $quotedLines = [];
        $allocatedDiscount = 0.0;

        foreach ($lines as $index => $line) {
            $gross = round((float) $line['line_total'], $decimals);
            $share = $subtotal > 0 ? round($gross / $subtotal * $discount, $decimals) : 0.0;

            if ($index === array_key_last($lines)) {
                $share = round($discount - $allocatedDiscount, $decimals);
            }

            $allocatedDiscount += $share;
            $net = max(0, round($gross - $share, $decimals));
            $item = $line['menu_item'] ?? null;
            $rule = $this->resolver->resolve($item instanceof MenuItem ? $item : null, $settings);

            $quotedLines[] = $this->quoteLine($net, $rule, $settings, $decimals, $item);
        }

        $serviceCharge = 0.0;

        if ($settings->service_charge_enabled && (float) $settings->service_charge_rate > 0) {
            $serviceCharge = round(($subtotal - $discount) * ((float) $settings->service_charge_rate / 100), $decimals);

            if ($settings->service_charge_taxable && $serviceCharge > 0) {
                $defaultRule = $this->resolver->resolve(null, $settings);
                $quotedService = $this->quoteLine($serviceCharge, $defaultRule, $settings, $decimals, null, true);
                $quotedLines[] = array_merge($quotedService, [
                    'name' => 'Service Charge',
                    'is_service_charge' => true,
                ]);
            }
        }

        $taxableAmount = round(array_sum(array_column($quotedLines, 'taxable_amount')), $decimals);
        $taxAmount = round(array_sum(array_column($quotedLines, 'tax_amount')), $decimals);
        $flatBreakdown = [];
        foreach ($quotedLines as $quotedLine) {
            $flatBreakdown = array_merge($flatBreakdown, $quotedLine['tax_breakdown']);
        }
        $breakdown = $this->mergeBreakdown($flatBreakdown, $decimals);

        $itemLines = array_values(array_filter($quotedLines, fn (array $line) => empty($line['is_service_charge'])));
        $serviceTax = 0.0;
        foreach ($quotedLines as $quotedLine) {
            if (! empty($quotedLine['is_service_charge'])) {
                $serviceTax = (float) $quotedLine['tax_amount'];
            }
        }

        $grandTotal = $settings->prices_include_tax
            ? round($subtotal - $discount + $serviceCharge + $serviceTax, $decimals)
            : round($subtotal - $discount + $serviceCharge + $taxAmount, $decimals);

        $applicable = ! $this->allNotApplicable($itemLines);
        $headlineRule = $this->resolver->resolve(null, $settings);

        return [
            'country' => $settings->country_code,
            'tax_rule' => $headlineRule->code,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'service_charge' => $serviceCharge,
            'service_charge_rate' => $settings->service_charge_enabled ? (float) $settings->service_charge_rate : 0.0,
            'service_charge_taxable' => $settings->service_charge_taxable,
            'taxable_amount' => $taxableAmount,
            'tax_amount' => $taxAmount,
            'tax_percent' => (float) $headlineRule->rate,
            'total_amount' => $grandTotal,
            'grand_total' => $grandTotal,
            'tax_applicable' => $applicable,
            'tax_label' => $applicable ? $headlineRule->tax_name : 'Not Applicable',
            'tax_display' => $applicable ? 'breakdown' : 'not_applicable',
            'tax_breakdown' => $applicable ? $breakdown : [],
            'prices_include_tax' => $settings->prices_include_tax,
            'order_type' => $orderType,
            'customer_type' => $customerType,
            'lines' => $itemLines,
            'snapshot' => [
                'country_code' => $settings->country_code,
                'supply_type' => $settings->supply_type,
                'prices_include_tax' => $settings->prices_include_tax,
                'tax_registered' => $settings->tax_registered,
                'tax_registration_number' => $settings->tax_registration_number,
                'tax_authority' => $settings->tax_authority,
                'tax_invoice_enabled' => $settings->tax_invoice_enabled,
                'applicable' => $applicable,
                'label' => $applicable ? $headlineRule->tax_name : 'VAT Not Currently Applicable',
                'display' => $applicable ? 'breakdown' : 'not_applicable',
                'headline_rate' => (float) $headlineRule->rate,
                'headline_rule_code' => $headlineRule->code,
                'headline_tax_type' => $headlineRule->tax_type,
                'taxable_amount' => $taxableAmount,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discount,
                'service_charge_amount' => $serviceCharge,
                'service_charge_rate' => $settings->service_charge_enabled ? (float) $settings->service_charge_rate : 0.0,
                'service_charge_taxable' => $settings->service_charge_taxable,
                'breakdown' => $applicable ? $breakdown : [],
                'rules' => array_values(array_map(fn (array $line) => $line['rule_snapshot'], $itemLines)),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function quoteLine(float $net, TaxRule $rule, TaxSetting $settings, int $decimals, ?MenuItem $item, bool $forceExclusive = false): array
    {
        $effectiveRate = in_array($rule->classification, ['not_applicable', 'exempt', 'out_of_scope'], true)
            ? 0.0
            : (float) $rule->rate;

        $inclusive = $settings->prices_include_tax && ! $forceExclusive && $effectiveRate > 0;

        if ($inclusive) {
            $taxable = round($net / (1 + ($effectiveRate / 100)), $decimals);
            $tax = round($net - $taxable, $decimals);
        } else {
            $taxable = $net;
            $tax = round($taxable * ($effectiveRate / 100), $decimals);
        }

        $breakdown = $this->componentBreakdown($rule, $taxable, $tax, $decimals, $effectiveRate);

        return [
            'menu_item_id' => $item?->id,
            'name' => $item?->name,
            'line_total' => $net,
            'taxable_amount' => $taxable,
            'tax_amount' => $tax,
            'tax_rule_code' => $rule->code,
            'tax_breakdown' => $breakdown,
            'rule_snapshot' => [
                'code' => $rule->code,
                'tax_name' => $rule->tax_name,
                'tax_type' => $rule->tax_type,
                'classification' => $rule->classification,
                'rate' => $effectiveRate,
                'itc' => $rule->itc,
                'family' => $rule->family,
                'supply_scope' => $rule->supply_scope,
                'components' => $breakdown,
            ],
        ];
    }

    /**
     * @return array<int, array{name: string, rate: float, amount: float}>
     */
    private function componentBreakdown(TaxRule $rule, float $taxable, float $tax, int $decimals, float $effectiveRate): array
    {
        $components = $rule->components;

        if (in_array($rule->classification, ['not_applicable', 'exempt', 'out_of_scope'], true)) {
            return [];
        }

        if ($rule->classification === 'zero_rated') {
            return [[
                'name' => 'Zero Rated',
                'rate' => 0.0,
                'amount' => 0.0,
            ]];
        }

        if ($components->isEmpty()) {
            return [[
                'name' => $rule->tax_name.' '.$rule->formatRate($effectiveRate).'%',
                'rate' => $effectiveRate,
                'amount' => $tax,
            ]];
        }

        $rows = [];
        $assigned = 0.0;
        $lastIndex = $components->count() - 1;

        foreach ($components->values() as $index => $component) {
            $rate = (float) $component->rate;
            $amount = $index === $lastIndex
                ? round($tax - $assigned, $decimals)
                : round($taxable * ($rate / 100), $decimals);
            $assigned += $amount;
            $rows[] = [
                'name' => $component->name.' '.$rule->formatRate($rate).'%',
                'rate' => $rate,
                'amount' => $amount,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array{name: string, rate: float, amount: float}>  $rows
     * @return array<int, array{name: string, rate: float, amount: float}>
     */
    private function mergeBreakdown(array $rows, int $decimals): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            if ($row === []) {
                continue;
            }
            $key = $row['name'];
            if (! isset($grouped[$key])) {
                $grouped[$key] = ['name' => $row['name'], 'rate' => (float) $row['rate'], 'amount' => 0.0];
            }
            $grouped[$key]['amount'] = round($grouped[$key]['amount'] + (float) $row['amount'], $decimals);
        }

        return array_values($grouped);
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function allNotApplicable(array $lines): bool
    {
        if ($lines === []) {
            $rule = $this->resolver->resolve(null, TaxSetting::current());

            return $rule->isNotApplicable();
        }

        foreach ($lines as $line) {
            if (($line['rule_snapshot']['classification'] ?? '') !== 'not_applicable') {
                return false;
            }
        }

        return true;
    }
}
