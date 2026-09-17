<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\User;
use Database\Seeders\TaxRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuItemFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TaxRuleSeeder::class);
    }

    public function test_admin_can_open_the_edit_menu_item_page(): void
    {
        $category = Category::query()->create(['name' => 'Food', 'slug' => 'food']);
        $item = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Chicken Biryani',
            'base_price' => 250,
            'is_active' => true,
        ]);
        $item->sizes()->create(['name' => 'Regular', 'price' => 250, 'sort_order' => 0]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('menu-items.edit', $item));

        $response->assertOk();
        $response->assertSee('Chicken Biryani');
        $response->assertSee('Regular');
        $response->assertSee('Tax category');
        $response->assertSee('Restaurant');
        $response->assertSee('Active');
    }

    public function test_admin_can_deactivate_a_size_option(): void
    {
        $category = Category::query()->create(['name' => 'Food', 'slug' => 'food']);
        $item = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Chicken Biryani',
            'base_price' => 250,
            'is_active' => true,
        ]);
        $item->sizes()->create(['name' => 'Small', 'price' => 200, 'sort_order' => 0, 'is_active' => true]);
        $item->sizes()->create(['name' => 'Large', 'price' => 300, 'sort_order' => 1, 'is_active' => true]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('menu-items.update', $item), [
            'category_id' => $category->id,
            'name' => 'Chicken Biryani',
            'description' => 'Spiced rice',
            'base_price' => 250,
            'is_active' => 1,
            'tax_rule_id' => '',
            'sizes' => [
                ['name' => 'Small', 'price' => 200, 'is_active' => '0'],
                ['name' => 'Large', 'price' => 300, 'is_active' => '1'],
            ],
        ])->assertRedirect(route('menu-items.index'));

        $this->assertFalse($item->sizes()->where('name', 'Small')->first()->is_active);
        $this->assertTrue($item->sizes()->where('name', 'Large')->first()->is_active);
        $this->assertNull($item->fresh()->tax_rule_id);
    }

    public function test_admin_can_save_a_single_portion_item_without_sizes(): void
    {
        $category = Category::query()->create(['name' => 'Food', 'slug' => 'food']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('menu-items.store'), [
            'category_id' => $category->id,
            'name' => 'Mango Lassi',
            'description' => 'One portion',
            'base_price' => 80,
            'is_active' => 1,
            'tax_rule_id' => '',
            'sizes' => [
                ['name' => '', 'price' => '', 'is_active' => '1'],
                ['name' => '', 'price' => '', 'is_active' => '0'],
            ],
        ])->assertRedirect(route('menu-items.index'));

        $item = MenuItem::query()->where('name', 'Mango Lassi')->first();
        $this->assertNotNull($item);
        $this->assertSame(0, $item->sizes()->count());
        $this->assertEquals(80, (float) $item->base_price);
    }
}
