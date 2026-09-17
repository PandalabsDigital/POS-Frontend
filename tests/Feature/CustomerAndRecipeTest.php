<?php

namespace Tests\Feature;

use App\Enums\OrderType;
use App\Models\Category;
use App\Models\Customer;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\TaxRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAndRecipeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TaxRuleSeeder::class);
    }

    public function test_checkout_saves_customer_name_and_phone(): void
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
            'customer_name' => 'Asha Patel',
            'customer_phone' => '98765 43210',
            'payment_method' => 'cash',
            'items' => [
                ['menu_item_id' => $item->id, 'quantity' => 1],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('customers', [
            'name' => 'Asha Patel',
            'phone_normalized' => '9876543210',
        ]);
        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Asha Patel',
            'customer_phone' => '98765 43210',
        ]);
    }

    public function test_same_phone_updates_existing_customer(): void
    {
        $category = Category::query()->create(['name' => 'Food', 'slug' => 'food']);
        $item = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Paneer Tikka',
            'base_price' => 180,
            'is_active' => true,
        ]);
        $cashier = User::factory()->create();
        $orders = app(OrderService::class);

        $orders->create($cashier, OrderType::Takeaway, [
            ['menu_item_id' => $item->id, 'quantity' => 1],
        ], null, 0, 'cash', 'Riya', '555-0100');

        $orders->create($cashier, OrderType::Takeaway, [
            ['menu_item_id' => $item->id, 'quantity' => 1],
        ], null, 0, 'cash', 'Riya Shah', '(555) 0100');

        $this->assertSame(1, Customer::query()->count());
        $this->assertSame('Riya Shah', Customer::query()->first()->name);
        $this->assertSame(2, Customer::query()->first()->orders()->count());
    }

    public function test_staff_can_open_recipes_and_see_course_types(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('recipes.index'));

        $response->assertOk();
        $response->assertSee('Starters');
        $response->assertSee('Main course');
        $response->assertSee('Desserts');
        $response->assertSee('Drinks');
        $response->assertSee('Fast food');
        $response->assertSee('Burgers');
        $response->assertSee('Pizza');
        $response->assertSee('Indian');
        $response->assertSee('youtube.com/results');
    }

    public function test_cashier_cannot_open_customers_list(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('customers.index'))
            ->assertForbidden();
    }

    public function test_delivery_order_requires_and_prints_address(): void
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
            'type' => OrderType::Delivery->value,
            'customer_name' => 'Asha Patel',
            'customer_phone' => '9876543210',
            'payment_method' => 'cash',
            'items' => [
                ['menu_item_id' => $item->id, 'quantity' => 1],
            ],
        ])->assertUnprocessable();

        $this->actingAs($cashier)->postJson(route('orders.store'), [
            'type' => OrderType::Delivery->value,
            'customer_name' => 'Asha Patel',
            'customer_phone' => '9876543210',
            'delivery_address' => "Building 12, Al Sadd\nNear City Center",
            'payment_method' => 'cash',
            'items' => [
                ['menu_item_id' => $item->id, 'quantity' => 1],
            ],
        ])->assertOk();

        $order = Order::query()->latest('id')->first();
        $this->assertSame("Building 12, Al Sadd\nNear City Center", $order->delivery_address);

        $this->actingAs($cashier)
            ->get(route('orders.receipt', $order))
            ->assertOk()
            ->assertSee('Deliver to')
            ->assertSee('Building 12, Al Sadd')
            ->assertSee('Near City Center');
    }
}
