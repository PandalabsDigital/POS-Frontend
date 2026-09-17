<?php

namespace Tests\Feature;

use App\Enums\OrderType;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\TaxRule;
use App\Models\TaxSetting;
use App\Models\User;
use App\Services\OrderService;
use App\Services\TaxEngine;
use Database\Seeders\TaxRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TaxRuleSeeder::class);
    }

    public function test_india_restaurant_service_uses_five_percent_cgst_sgst(): void
    {
        $quote = app(TaxEngine::class)->calculateTax([['line_total' => 1000]]);

        $this->assertEquals(1000.0, $quote['taxable_amount']);
        $this->assertEquals(50.0, $quote['tax_amount']);
        $this->assertEquals(1050.0, $quote['grand_total']);
        $this->assertEquals('CGST 2.5%', $quote['tax_breakdown'][0]['name']);
        $this->assertEquals(25.0, $quote['tax_breakdown'][0]['amount']);
        $this->assertEquals('SGST 2.5%', $quote['tax_breakdown'][1]['name']);
        $this->assertEquals(25.0, $quote['tax_breakdown'][1]['amount']);
    }

    public function test_india_interstate_uses_igst(): void
    {
        TaxSetting::current()->update(['supply_type' => 'inter']);

        $quote = app(TaxEngine::class)->calculateTax([['line_total' => 1000]]);

        $this->assertEquals(50.0, $quote['tax_amount']);
        $this->assertEquals('IGST 5%', $quote['tax_breakdown'][0]['name']);
        $this->assertEquals(50.0, $quote['tax_breakdown'][0]['amount']);
    }

    public function test_tax_inclusive_uses_reverse_calculation(): void
    {
        TaxSetting::current()->update(['prices_include_tax' => true]);

        $quote = app(TaxEngine::class)->calculateTax([['line_total' => 105]]);

        $this->assertEquals(100.0, $quote['taxable_amount']);
        $this->assertEquals(5.0, $quote['tax_amount']);
        $this->assertEquals(105.0, $quote['grand_total']);
    }

    public function test_discount_applies_before_tax(): void
    {
        $quote = app(TaxEngine::class)->calculateTax([['line_total' => 1000]], 100);

        $this->assertEquals(900.0, $quote['taxable_amount']);
        $this->assertEquals(45.0, $quote['tax_amount']);
        $this->assertEquals(945.0, $quote['grand_total']);
    }

    public function test_qatar_is_not_shown_as_zero_rated_vat(): void
    {
        $this->useCountry('QA');

        $quote = app(TaxEngine::class)->calculateTax([['line_total' => 100]]);

        $this->assertFalse($quote['tax_applicable']);
        $this->assertSame('not_applicable', $quote['tax_display']);
        $this->assertSame('Not Applicable', $quote['tax_label']);
        $this->assertSame('VAT Not Currently Applicable', $quote['snapshot']['label']);
        $this->assertSame([], $quote['tax_breakdown']);
        $this->assertEquals(0.0, $quote['tax_amount']);
        $this->assertEquals(100.0, $quote['grand_total']);
    }

    public function test_uae_standard_vat_is_five_percent(): void
    {
        $this->useCountry('AE');

        $quote = app(TaxEngine::class)->calculateTax([['line_total' => 100]]);

        $this->assertEquals(5.0, $quote['tax_amount']);
        $this->assertEquals(105.0, $quote['grand_total']);
        $this->assertEquals('VAT 5%', $quote['tax_breakdown'][0]['name']);
    }

    public function test_saudi_standard_vat_is_fifteen_percent(): void
    {
        $this->useCountry('SA');

        $quote = app(TaxEngine::class)->calculateTax([['line_total' => 100]]);

        $this->assertEquals(15.0, $quote['tax_amount']);
        $this->assertEquals(115.0, $quote['grand_total']);
    }

    public function test_historical_invoice_keeps_snapshot_after_rate_change(): void
    {
        $category = Category::query()->create(['name' => 'Food', 'slug' => 'food']);
        $item = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Chicken Biryani',
            'base_price' => 1000,
            'is_active' => true,
        ]);
        $cashier = User::factory()->create();

        $order = app(OrderService::class)->create($cashier, OrderType::DineIn, [
            ['menu_item_id' => $item->id, 'quantity' => 1],
        ]);

        $this->assertEquals(50.0, (float) $order->tax_amount);

        $rule = TaxRule::query()->where('code', 'in_gst_restaurant_5')->first();
        $rule->update(['rate' => 12]);
        $rule->components()->update(['rate' => 6]);

        $order->refresh();
        $this->assertEquals(50.0, (float) $order->tax_amount);
        $this->assertEquals(5.0, $order->tax_snapshot['headline_rate']);
        $this->assertEquals(25.0, $order->tax_snapshot['breakdown'][0]['amount']);
    }

    private function useCountry(string $code): void
    {
        $rule = TaxRule::query()
            ->where('country_code', $code)
            ->where('is_default', true)
            ->firstOrFail();

        TaxSetting::current()->update([
            'country_code' => $code,
            'default_tax_rule_id' => $rule->id,
            'supply_type' => 'intra',
            'prices_include_tax' => false,
        ]);
    }
}
