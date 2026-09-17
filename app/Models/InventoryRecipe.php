<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'menu_item_id',
    'output_item_id',
    'name',
    'type',
    'yield_quantity',
    'yield_unit_id',
    'waste_percent',
    'instructions',
    'is_active',
    'version',
])]
class InventoryRecipe extends Model
{
    protected function casts(): array
    {
        return [
            'yield_quantity' => 'decimal:4',
            'waste_percent' => 'decimal:2',
            'is_active' => 'boolean',
            'version' => 'integer',
        ];
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function outputItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'output_item_id');
    }

    public function yieldUnit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'yield_unit_id');
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(InventoryRecipeItem::class)->orderBy('sort_order');
    }

    public function isProduction(): bool
    {
        return $this->type === 'production';
    }
}
