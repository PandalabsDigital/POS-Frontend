<?php

namespace Database\Seeders;

use App\Models\InventoryCategory;
use App\Models\InventoryLocation;
use App\Models\InventoryUnit;
use Illuminate\Database\Seeder;

class InventoryCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'kg', 'name' => 'Kilogram', 'dimension' => 'mass', 'to_base' => 1000],
            ['code' => 'g', 'name' => 'Gram', 'dimension' => 'mass', 'to_base' => 1],
            ['code' => 'lb', 'name' => 'Pound', 'dimension' => 'mass', 'to_base' => 453.592],
            ['code' => 'oz', 'name' => 'Ounce', 'dimension' => 'mass', 'to_base' => 28.3495],
            ['code' => 'L', 'name' => 'Litre', 'dimension' => 'volume', 'to_base' => 1000],
            ['code' => 'ml', 'name' => 'Millilitre', 'dimension' => 'volume', 'to_base' => 1],
            ['code' => 'gallon', 'name' => 'Gallon', 'dimension' => 'volume', 'to_base' => 3785.41],
            ['code' => 'pcs', 'name' => 'Piece', 'dimension' => 'count', 'to_base' => 1],
            ['code' => 'dozen', 'name' => 'Dozen', 'dimension' => 'count', 'to_base' => 12],
            ['code' => 'box', 'name' => 'Box', 'dimension' => 'count', 'to_base' => 1],
            ['code' => 'packet', 'name' => 'Packet', 'dimension' => 'count', 'to_base' => 1],
            ['code' => 'bottle', 'name' => 'Bottle', 'dimension' => 'count', 'to_base' => 1],
            ['code' => 'can', 'name' => 'Can', 'dimension' => 'count', 'to_base' => 1],
            ['code' => 'tray', 'name' => 'Tray', 'dimension' => 'count', 'to_base' => 1],
            ['code' => 'carton', 'name' => 'Carton', 'dimension' => 'count', 'to_base' => 1],
        ];

        foreach ($units as $unit) {
            InventoryUnit::query()->updateOrCreate(['code' => $unit['code']], $unit + ['is_custom' => false]);
        }

        $categories = [
            'Food' => ['Meat', 'Poultry', 'Seafood', 'Vegetables', 'Fruits', 'Dairy', 'Grains', 'Rice', 'Flour', 'Spices', 'Sauces', 'Oils', 'Frozen Food'],
            'Beverages' => ['Soft Drinks', 'Juices', 'Water', 'Coffee', 'Tea'],
            'Packaging' => ['Takeaway Boxes', 'Cups', 'Lids', 'Bags', 'Cutlery', 'Napkins'],
            'Cleaning' => ['Dishwashing', 'Sanitizers', 'Cleaning Chemicals', 'Gloves'],
        ];

        $sort = 0;
        foreach ($categories as $group => $names) {
            foreach ($names as $name) {
                InventoryCategory::query()->updateOrCreate(
                    ['group_name' => $group, 'name' => $name],
                    ['sort_order' => $sort++, 'is_active' => true],
                );
            }
        }

        $locations = [
            ['name' => 'Main Kitchen', 'code' => 'kitchen', 'is_default' => true],
            ['name' => 'Bar', 'code' => 'bar', 'is_default' => false],
            ['name' => 'Freezer', 'code' => 'freezer', 'is_default' => false],
            ['name' => 'Dry Storage', 'code' => 'dry', 'is_default' => false],
            ['name' => 'Warehouse', 'code' => 'warehouse', 'is_default' => false],
        ];

        foreach ($locations as $location) {
            InventoryLocation::query()->updateOrCreate(['code' => $location['code']], $location + ['is_active' => true]);
        }
    }
}
