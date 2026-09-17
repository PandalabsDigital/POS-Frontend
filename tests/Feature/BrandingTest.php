<?php

namespace Tests\Feature;

use App\Enums\OrderType;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\TaxRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TaxRuleSeeder::class);
    }

    public function test_receipt_uses_restaurant_logo_and_restassured_powered_by(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->put(route('settings.update'), $this->settingsPayload([
            'restaurant_name' => 'Khansahab',
            'receipt_footer' => 'Thank you',
            'restaurant_logo' => UploadedFile::fake()->image('khansahab.png', 240, 120),
        ]))->assertRedirect(route('settings.edit'));

        $this->assertNotEmpty(Setting::get('restaurant_logo'));
        Storage::disk('public')->assertExists(Setting::get('restaurant_logo'));

        $order = $this->placeOrder();
        $this->actingAs($admin)
            ->get(route('orders.receipt', $order))
            ->assertOk()
            ->assertSee('receipt-restaurant-logo', false)
            ->assertSee('/storage/branding/', false)
            ->assertSee('Khansahab')
            ->assertSee('Thank you')
            ->assertSee('Powered by')
            ->assertSee('RestAssured')
            ->assertSee('images/logo-icon.jpg');
    }

    public function test_sidebar_uses_full_restassured_lockup(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('images/logo.jpg')
            ->assertSee('alt="RestAssured"', false)
            ->assertDontSee('brand-icon brand-icon-lg', false);
    }

    public function test_dashboard_shows_restaurant_logo_not_restassured_icon(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->put(route('settings.update'), $this->settingsPayload([
            'restaurant_name' => 'Khansahab',
            'restaurant_logo' => UploadedFile::fake()->image('khansahab.png', 240, 120),
        ]))->assertRedirect(route('settings.edit'));

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('dashboard-restaurant-logo', false)
            ->assertSee('/storage/branding/', false)
            ->assertSee('images/logo.jpg');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function settingsPayload(array $overrides = []): array
    {
        return array_merge([
            'restaurant_name' => 'Khansahab',
            'currency_code' => 'INR',
            'address' => '',
            'city' => '',
            'phone' => '',
            'email' => '',
            'website' => '',
            'opening_hours' => '',
            'receipt_footer' => 'Thank you',
            'invoice_prefix' => 'INV',
            'timezone' => 'Asia/Kolkata',
            'default_order_type' => OrderType::DineIn->value,
            'show_cashier_on_receipt' => '1',
            'inventory_enabled' => '1',
            'inventory_auto_deduct' => '1',
            'inventory_allow_negative' => '0',
            'inventory_auto_disable_menu' => '0',
            'inventory_enable_batches' => '1',
            'inventory_enable_expiry' => '1',
            'inventory_low_stock_alerts' => '1',
            'inventory_food_cost_target' => '30',
        ], $overrides);
    }

    private function placeOrder(): Order
    {
        $category = Category::query()->create(['name' => 'Food', 'slug' => 'food']);
        $item = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Veg Biryani',
            'base_price' => 200,
            'is_active' => true,
        ]);
        $cashier = User::factory()->create();

        $this->actingAs($cashier)->postJson(route('orders.store'), [
            'type' => OrderType::DineIn->value,
            'customer_name' => 'Guest',
            'customer_phone' => '9876543210',
            'payment_method' => 'cash',
            'items' => [['menu_item_id' => $item->id, 'quantity' => 1]],
        ])->assertOk();

        return Order::query()->latest('id')->firstOrFail();
    }
}
