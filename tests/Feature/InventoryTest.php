<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryRecipe;
use App\Models\InventoryTransaction;
use App\Models\InventoryUnit;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\OrderService;
use Database\Seeders\InventoryCatalogSeeder;
use Database\Seeders\TaxRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TaxRuleSeeder::class);
        $this->seed(InventoryCatalogSeeder::class);
    }

    public function test_purchase_increases_stock(): void
    {
        [$admin, $chicken, $kg] = $this->chickenFixture();
        $this->actingAs($admin)->post(route('inventory.purchases.store'), [
            'inventory_location_id' => InventoryLocation::defaultLocation()->id,
            'received_date' => now()->toDateString(),
            'lines' => [[
                'inventory_item_id' => $chicken->id,
                'unit_id' => $kg->id,
                'quantity' => 10,
                'unit_cost' => 350,
            ]],
        ])->assertRedirect();

        $this->assertEquals(10, $chicken->fresh()->onHand());
    }

    public function test_unit_conversion_and_pos_recipe_consumption(): void
    {
        [$admin, $chicken, $kg, $g, $burger] = $this->burgerFixture();
        app(InventoryService::class)->receiveStock(
            $chicken, 10, $kg, 100, $admin, InventoryLocation::defaultLocation()
        );

        $this->actingAs($admin)->postJson(route('orders.store'), [
            'type' => OrderType::DineIn->value,
            'customer_name' => 'Guest',
            'customer_phone' => '9876543210',
            'payment_method' => 'cash',
            'items' => [['menu_item_id' => $burger->id, 'quantity' => 2]],
        ])->assertOk();

        $this->assertEqualsWithDelta(9.7, $chicken->fresh()->onHand(), 0.001);
    }

    public function test_insufficient_stock_blocks_sale(): void
    {
        [$admin, $chicken, $kg, $g, $burger] = $this->burgerFixture();
        app(InventoryService::class)->receiveStock(
            $chicken, 0.1, $kg, 100, $admin, InventoryLocation::defaultLocation()
        );

        $this->actingAs($admin)->postJson(route('orders.store'), [
            'type' => OrderType::DineIn->value,
            'customer_name' => 'Guest',
            'customer_phone' => '9876543210',
            'payment_method' => 'cash',
            'items' => [['menu_item_id' => $burger->id, 'quantity' => 2]],
        ])->assertUnprocessable();
    }

    public function test_wastage_and_transfer_and_stocktake(): void
    {
        [$admin, $chicken, $kg] = $this->chickenFixture();
        $service = app(InventoryService::class);
        $kitchen = InventoryLocation::query()->where('code', 'kitchen')->first();
        $warehouse = InventoryLocation::query()->where('code', 'warehouse')->first();
        $service->receiveStock($chicken, 20, $kg, 50, $admin, $kitchen);

        $this->actingAs($admin)->post(route('inventory.wastage.store'), [
            'inventory_item_id' => $chicken->id,
            'inventory_location_id' => $kitchen->id,
            'unit_id' => $kg->id,
            'quantity' => 2,
            'reason' => 'spoiled',
        ])->assertRedirect();
        $this->assertEqualsWithDelta(18, $service->getStockBalance($chicken, $kitchen), 0.001);

        $this->actingAs($admin)->post(route('inventory.transfers.store'), [
            'from_location_id' => $kitchen->id,
            'to_location_id' => $warehouse->id,
            'lines' => [[
                'inventory_item_id' => $chicken->id,
                'unit_id' => $kg->id,
                'quantity' => 5,
            ]],
        ])->assertRedirect();
        $this->assertEqualsWithDelta(13, $service->getStockBalance($chicken, $kitchen), 0.001);
        $this->assertEqualsWithDelta(5, $service->getStockBalance($chicken, $warehouse), 0.001);

        $service->adjustStock($chicken, 18, $admin, $kitchen, 'physical_count');
        $this->assertEqualsWithDelta(18, $service->getStockBalance($chicken, $kitchen), 0.001);
    }

    public function test_refund_reverses_consumption_without_rewriting_history(): void
    {
        [$admin, $chicken, $kg, $g, $burger] = $this->burgerFixture();
        $service = app(InventoryService::class);
        $service->receiveStock($chicken, 10, $kg, 100, $admin, InventoryLocation::defaultLocation());

        $this->actingAs($admin)->postJson(route('orders.store'), [
            'type' => OrderType::DineIn->value,
            'customer_name' => 'Guest',
            'customer_phone' => '5550100111',
            'payment_method' => 'cash',
            'items' => [['menu_item_id' => $burger->id, 'quantity' => 1]],
        ])->assertOk();

        $order = Order::query()->first();
        $this->assertEqualsWithDelta(9.85, $chicken->fresh()->onHand(), 0.001);

        app(OrderService::class)->refund($order, $admin);
        $this->assertSame(OrderStatus::Refunded, $order->fresh()->status);
        $this->assertEqualsWithDelta(10, $chicken->fresh()->onHand(), 0.001);
        $this->assertGreaterThan(1, InventoryTransaction::query()->count());
    }

    public function test_weighted_average_cost(): void
    {
        [$admin, $chicken, $kg] = $this->chickenFixture();
        $service = app(InventoryService::class);
        $loc = InventoryLocation::defaultLocation();
        $service->receiveStock($chicken, 10, $kg, 100, $admin, $loc);
        $service->receiveStock($chicken, 10, $kg, 200, $admin, $loc);
        $this->assertEqualsWithDelta(150, (float) $chicken->fresh()->average_cost, 0.01);
    }

    public function test_cashier_cannot_open_inventory(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('inventory.dashboard'))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: InventoryItem, 2: InventoryUnit}
     */
    private function chickenFixture(): array
    {
        $kg = InventoryUnit::query()->where('code', 'kg')->first();
        $admin = User::factory()->admin()->create();
        $chicken = InventoryItem::query()->create([
            'name' => 'Chicken Breast',
            'stock_unit_id' => $kg->id,
            'purchase_unit_id' => $kg->id,
            'purchase_to_stock_factor' => 1,
            'minimum_stock' => 10,
            'is_active' => true,
        ]);

        return [$admin, $chicken, $kg];
    }

    /**
     * @return array{0: User, 1: InventoryItem, 2: InventoryUnit, 3: InventoryUnit, 4: MenuItem}
     */
    private function burgerFixture(): array
    {
        [$admin, $chicken, $kg] = $this->chickenFixture();
        $g = InventoryUnit::query()->where('code', 'g')->first();
        $category = Category::query()->create(['name' => 'Mains', 'slug' => 'mains']);
        $burger = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Chicken Burger',
            'base_price' => 12,
            'is_active' => true,
        ]);
        $recipe = InventoryRecipe::query()->create([
            'menu_item_id' => $burger->id,
            'name' => 'Chicken Burger',
            'type' => 'sale',
            'yield_quantity' => 1,
            'is_active' => true,
        ]);
        $recipe->ingredients()->create([
            'inventory_item_id' => $chicken->id,
            'quantity' => 150,
            'unit_id' => $g->id,
            'waste_percent' => 0,
            'is_essential' => true,
        ]);

        return [$admin, $chicken, $kg, $g, $burger];
    }
}
