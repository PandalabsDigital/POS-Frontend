<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'inventory_recipe_id',
    'inventory_item_id',
    'quantity',
    'unit_id',
    'waste_percent',
    'is_essential',
    'sort_order',
])]
class InventoryRecipeItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'waste_percent' => 'decimal:2',
            'is_essential' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(InventoryRecipe::class, 'inventory_recipe_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'unit_id');
    }

    public function effectiveQuantity(): float
    {
        $waste = 1 + ((float) $this->waste_percent / 100);

        return round((float) $this->quantity * $waste, 4);
    }
}
