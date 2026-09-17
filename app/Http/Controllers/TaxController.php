<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTaxRuleRequest;
use App\Http\Requests\UpdateTaxSettingsRequest;
use App\Models\Setting;
use App\Models\TaxAuditLog;
use App\Models\TaxRule;
use App\Models\TaxSetting;
use App\Services\ReportService;
use App\Services\TaxAuditLogger;
use App\Services\TaxReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxController extends Controller
{
    public function edit(): View
    {
        $settings = TaxSetting::current();
        $rules = TaxRule::query()
            ->with('components')
            ->where('country_code', $settings->country_code)
            ->orderByDesc('is_default')
            ->orderBy('rate')
            ->orderBy('tax_type')
            ->get();

        return view('taxes.edit', [
            'settings' => $settings,
            'rules' => $rules,
            'countries' => config('taxation.countries'),
            'currencies' => config('currencies'),
            'currencyCode' => Setting::currencyCode(),
        ]);
    }

    public function update(UpdateTaxSettingsRequest $request, TaxAuditLogger $audit): RedirectResponse
    {
        $settings = TaxSetting::current();
        $before = $settings->toArray();
        $country = $request->validated('country_code');
        $rule = TaxRule::query()->findOrFail($request->validated('default_tax_rule_id'));

        if ($rule->country_code !== $country) {
            $rule = TaxRule::query()
                ->where('country_code', $country)
                ->where('is_default', true)
                ->firstOrFail();
        }

        $authority = $request->validated('tax_authority') ?: config('taxation.countries.'.$country.'.authority');

        $settings->update([
            'country_code' => $country,
            'tax_registered' => $request->boolean('tax_registered'),
            'tax_registration_number' => $request->validated('tax_registration_number'),
            'tax_authority' => $authority,
            'tax_invoice_enabled' => $request->boolean('tax_invoice_enabled'),
            'prices_include_tax' => $request->boolean('prices_include_tax'),
            'default_tax_rule_id' => $rule->id,
            'supply_type' => $request->validated('supply_type'),
            'service_charge_enabled' => $request->boolean('service_charge_enabled'),
            'service_charge_rate' => $request->validated('service_charge_rate') ?? 0,
            'service_charge_taxable' => $request->boolean('service_charge_taxable'),
        ]);

        TaxRule::query()->where('country_code', $country)->update(['is_default' => false]);
        $rule->update(['is_default' => true]);

        Setting::put('currency_code', $request->validated('currency_code'));

        $audit->record(
            'settings_updated',
            $before,
            $settings->fresh()->toArray(),
            'Restaurant tax settings updated',
            TaxSetting::class,
            $settings->id,
            $country,
        );

        return redirect()->route('taxes.edit')->with('success', 'Taxation settings saved. Historical invoices are unchanged.');
    }

    public function updateRule(UpdateTaxRuleRequest $request, TaxRule $taxRule, TaxAuditLogger $audit): RedirectResponse
    {
        $before = [
            'rate' => (float) $taxRule->rate,
            'is_active' => $taxRule->is_active,
            'tax_type' => $taxRule->tax_type,
        ];

        $newRate = (float) $request->validated('rate');
        $oldRate = (float) $taxRule->rate;

        $taxRule->update([
            'rate' => $newRate,
            'is_active' => $request->boolean('is_active', $taxRule->is_active),
        ]);

        if ($taxRule->components()->exists() && $oldRate > 0) {
            foreach ($taxRule->components as $component) {
                $share = (float) $component->rate / $oldRate;
                $component->update(['rate' => round($newRate * $share, 3)]);
            }
        } elseif ($taxRule->components()->exists() && $taxRule->components()->count() === 1) {
            $taxRule->components()->first()?->update(['rate' => $newRate]);
        }

        $audit->record(
            'rule_updated',
            $before,
            ['rate' => $newRate, 'is_active' => $taxRule->is_active, 'tax_type' => $taxRule->tax_type],
            $request->validated('reason'),
            TaxRule::class,
            $taxRule->id,
            $taxRule->country_code,
        );

        return redirect()->route('taxes.edit')->with('success', 'Tax rule updated. Existing invoices still use the rates stored on each receipt.');
    }

    public function makeDefault(TaxRule $taxRule, TaxAuditLogger $audit): RedirectResponse
    {
        $settings = TaxSetting::current();
        $before = ['default_tax_rule_id' => $settings->default_tax_rule_id];

        TaxRule::query()->where('country_code', $taxRule->country_code)->update(['is_default' => false]);
        $taxRule->update(['is_default' => true, 'is_active' => true]);
        $settings->update([
            'default_tax_rule_id' => $taxRule->id,
            'country_code' => $taxRule->country_code,
        ]);

        $audit->record(
            'default_rule_changed',
            $before,
            ['default_tax_rule_id' => $taxRule->id, 'code' => $taxRule->code],
            'Default restaurant tax rule changed',
            TaxRule::class,
            $taxRule->id,
            $taxRule->country_code,
        );

        return redirect()->route('taxes.edit')->with('success', 'Default tax rule updated.');
    }

    public function reports(Request $request, ReportService $ranges, TaxReportService $taxes): View
    {
        $range = $ranges->range($request->query('from'), $request->query('to'));
        $data = $taxes->build($range['from'], $range['to']);

        return view('taxes.reports', [
            'from' => $range['from']->toDateString(),
            'to' => $range['to']->toDateString(),
            ...$data,
        ]);
    }

    public function audit(): View
    {
        return view('taxes.audit', [
            'logs' => TaxAuditLog::query()->with('user')->latest()->paginate(30),
        ]);
    }
}
