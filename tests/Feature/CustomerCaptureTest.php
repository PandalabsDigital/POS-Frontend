<?php

namespace Tests\Feature;

use App\Enums\CustomerCaptureStatus;
use App\Enums\OrderType;
use App\Models\Category;
use App\Models\Customer;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\TaxRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCaptureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TaxRuleSeeder::class);
    }

    public function test_name_and_phone_are_required_unless_declined(): void
    {
        [$cashier, $item] = $this->posFixture();

        $this->actingAs($cashier)->postJson(route('orders.store'), [
            'type' => OrderType::DineIn->value,
            'payment_method' => 'cash',
            'items' => [['menu_item_id' => $item->id, 'quantity' => 1]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_name', 'customer_phone']);
    }

    public function test_declined_requires_a_written_reason(): void
    {
        [$cashier, $item] = $this->posFixture();

        $this->actingAs($cashier)->postJson(route('orders.store'), [
            'type' => OrderType::DineIn->value,
            'customer_declined' => true,
            'customer_capture_reason' => 'no',
            'payment_method' => 'cash',
            'items' => [['menu_item_id' => $item->id, 'quantity' => 1]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('customer_capture_reason');

        $this->actingAs($cashier)->postJson(route('orders.store'), [
            'type' => OrderType::DineIn->value,
            'customer_declined' => true,
            'customer_capture_reason' => 'Guest did not want to share a phone number',
            'payment_method' => 'cash',
            'items' => [['menu_item_id' => $item->id, 'quantity' => 1]],
        ])->assertOk();

        $order = Order::query()->first();
        $this->assertNull($order->customer_id);
        $this->assertSame(CustomerCaptureStatus::CustomerDeclined, $order->customer_capture_status);
        $this->assertSame('Guest did not want to share a phone number', $order->customer_capture_reason);
        $this->assertSame(0, Customer::query()->count());
    }

    /**
     * @return array{0: User, 1: MenuItem}
     */
    private function posFixture(): array
    {
        $category = Category::query()->create(['name' => 'Food', 'slug' => 'food']);
        $item = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Veg Biryani',
            'base_price' => 200,
            'is_active' => true,
        ]);

        return [User::factory()->create(), $item];
    }
}
