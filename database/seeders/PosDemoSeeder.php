<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Condiment;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PosDemoSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@pos.test'],
            ['name' => 'Admin User', 'password' => Hash::make('password'), 'role' => Role::Admin]
        );

        User::query()->updateOrCreate(
            ['email' => 'cashier@pos.test'],
            ['name' => 'Sarah Cashier', 'password' => Hash::make('password'), 'role' => Role::Cashier]
        );

        Setting::put('restaurant_name', 'RestAssured');
        Setting::put('currency_code', 'USD');

        Tax::query()->updateOrCreate(
            ['id' => 1],
            ['name' => 'Sales Tax', 'percent' => 8.00, 'is_enabled' => true]
        );

        MenuItem::query()->get()->each(function (MenuItem $item): void {
            if ($item->image_path) {
                Storage::disk('public')->delete($item->image_path);
            }
            $item->delete();
        });

        $categories = collect([
            'Burgers',
            'Pizza',
            'Drinks',
            'Desserts',
        ])->map(function (string $name, int $index) {
            return Category::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $index, 'is_active' => true]
            );
        })->keyBy('name');

        $menu = [
            ['Burgers', 'Veggie Burger', 'Chickpea patty, lettuce, tomato, pickles.', 6.50, ['Small' => 6.50, 'Medium' => 7.50, 'Large' => 8.90], 'veggie-burger.png'],
            ['Burgers', 'Paneer Burger', 'Grilled paneer tikka, mint yogurt, onion.', 7.25, ['Small' => 7.25, 'Medium' => 8.50, 'Large' => 9.75], 'paneer-burger.png'],
            ['Pizza', 'Margherita Pizza', 'Tomato, mozzarella, and fresh basil.', 10.00, ['Small' => 10.00, 'Medium' => 13.50, 'Large' => 16.90], 'margherita-pizza.png'],
            ['Pizza', 'Veggie Supreme Pizza', 'Peppers, olives, mushrooms, corn, onion.', 11.50, ['Small' => 11.50, 'Medium' => 14.90, 'Large' => 18.25], 'veggie-supreme-pizza.png'],
            ['Drinks', 'Mango Lassi', 'Chilled mango yogurt drink.', 2.40, ['Small' => 2.40, 'Medium' => 3.10, 'Large' => 3.80], 'mango-lassi.png'],
            ['Drinks', 'Fresh Lemonade', 'House-made lemonade with mint.', 2.20, ['Small' => 2.20, 'Medium' => 2.80, 'Large' => 3.40], 'fresh-lemonade.png'],
            ['Desserts', 'Chocolate Brownie', 'Warm vegetarian brownie with ganache.', 3.50, ['Small' => 3.50, 'Medium' => 4.25, 'Large' => 5.00], 'chocolate-brownie.png'],
            ['Desserts', 'Mango Kulfi', 'Saffron mango kulfi with pistachio.', 3.20, ['Small' => 3.20, 'Medium' => 3.90, 'Large' => 4.60], 'mango-kulfi.png'],
        ];

        Storage::disk('public')->makeDirectory('menu');

        foreach ($menu as [$categoryName, $name, $description, $base, $sizes, $image]) {
            $item = MenuItem::query()->create([
                'category_id' => $categories[$categoryName]->id,
                'name' => $name,
                'description' => $description,
                'base_price' => $base,
                'is_active' => true,
                'image_path' => $this->storeMenuImage($image),
            ]);

            $sort = 0;
            foreach ($sizes as $sizeName => $price) {
                $item->sizes()->create([
                    'name' => $sizeName,
                    'price' => $price,
                    'sort_order' => $sort++,
                ]);
            }
        }

        foreach ([
            ['Extra Cheese', 1.25],
            ['Extra Sauce', 0.75],
            ['Jalapenos', 0.90],
            ['Fries', 2.50],
        ] as [$name, $price]) {
            Condiment::query()->updateOrCreate(
                ['name' => $name],
                ['price' => $price, 'is_active' => true]
            );
        }
    }

    private function storeMenuImage(string $filename): string
    {
        $path = 'menu/'.$filename;
        $source = public_path('images/menu/'.$filename);

        if (is_file($source)) {
            Storage::disk('public')->put($path, File::get($source));
        }

        return $path;
    }
}
