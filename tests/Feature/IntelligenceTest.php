<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use App\Services\Intelligence\ProductAnalytics;
use App\Services\Intelligence\ReportFilter;
use App\Services\Intelligence\SalesMetrics;
use Database\Seeders\TaxRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IntelligenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TaxRuleSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-09-13 12:00:00'));
    }

    public function test_cashier_cannot_open_reports_or_analytics(): void
    {
        $cashier = User::factory()->create();

        $this->actingAs($cashier)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('analytics.index'))->assertForbidden();
    }

    public function test_net_sales_is_gross_minus_discounts_minus_refunds(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->menuItem();
        $this->placeOrder($item, 0, 'cash');
        $discounted = $this->placeOrder($item, 25, 'card');
        $refunded = $this->placeOrder($item, 0, 'cash');
        $this->actingAs($admin)->post(route('orders.refund', $refunded))->assertRedirect();

        $filter = ReportFilter::fromRequest(Request::create('/', 'GET', [
            'preset' => 'today',
            'compare' => 'none',
        ]));
        $snapshot = app(SalesMetrics::class)->snapshot($filter);

        $completed = Order::query()->where('status', OrderStatus::Completed)->get();
        $gross = (float) $completed->sum('subtotal');
        $discounts = (float) $completed->sum('discount_amount');
        $refunds = (float) Order::query()->where('status', OrderStatus::Refunded)->sum('grand_total');

        $this->assertEqualsWithDelta($gross, $snapshot['gross'], 0.01);
        $this->assertEqualsWithDelta($discounts, $snapshot['discounts'], 0.01);
        $this->assertEqualsWithDelta($refunds, $snapshot['refunds'], 0.01);
        $this->assertEqualsWithDelta($gross - $discounts - $refunds, $snapshot['net'], 0.01);
        $this->assertSame(2, $snapshot['orders']);
        $this->assertEqualsWithDelta(25.0, (float) $discounted->discount_percent, 0.01);
        $this->assertEqualsWithDelta(((float) $discounted->subtotal) * 0.25, (float) $discounted->discount_amount, 0.02);
    }

    public function test_product_report_counts_units_from_order_items(): void
    {
        $item = $this->menuItem();
        $this->placeOrder($item, 0, 'upi', 2);
        $this->placeOrder($item, 0, 'upi', 1);

        $filter = ReportFilter::fromRequest(Request::create('/', 'GET', ['preset' => 'today', 'compare' => 'none']));
        $rows = app(ProductAnalytics::class)->products($filter);

        $this->assertCount(1, $rows);
        $this->assertSame(3.0, (float) $rows->first()->units);
        $this->assertSame('uncosted', $rows->first()->classification);
    }

    public function test_analytics_explains_empty_range_instead_of_inventing_numbers(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('analytics.index', ['preset' => 'today']))
            ->assertOk()
            ->assertSee('No completed sales in this range')
            ->assertDontSee('static demo');
    }

    public function test_daily_summary_and_csv_use_live_orders(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->menuItem();
        $this->placeOrder($item, 0, 'cash');

        $this->actingAs($admin)
            ->get(route('reports.show', ['report' => 'daily', 'preset' => 'today']))
            ->assertOk()
            ->assertSee('Gross sales')
            ->assertSee('Not recorded');

        $csv = $this->actingAs($admin)
            ->get(route('reports.show', ['report' => 'daily', 'preset' => 'today', 'export' => 'csv']));
        $csv->assertOk();
        $this->assertStringContainsString('Gross', $csv->streamedContent());
    }

    private function menuItem(): MenuItem
    {
        $category = Category::query()->create(['name' => 'Food', 'slug' => 'food-'.uniqid()]);

        return MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Veg Biryani',
            'base_price' => 200,
            'is_active' => true,
        ]);
    }

    private function placeOrder(MenuItem $item, float $discount, string $payment, int $qty = 1): Order
    {
        $cashier = User::factory()->create();

        $this->actingAs($cashier)->postJson(route('orders.store'), [
            'type' => OrderType::DineIn->value,
            'customer_name' => 'Guest',
            'customer_phone' => '98765'.random_int(10000, 99999),
            'payment_method' => $payment,
            'discount_percent' => $discount,
            'items' => [['menu_item_id' => $item->id, 'quantity' => $qty]],
        ])->assertOk();

        return Order::query()->latest('id')->firstOrFail();
    }
}
