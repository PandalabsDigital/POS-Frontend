<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'inventory_stocktake_id',
    'inventory_item_id',
    'expected_quantity',
    'counted_quantity',
    'difference',
])]
class InventoryStocktakeItem extends Model
{
    protected function casts(): array
    {
        return [
            'expected_quantity' => 'decimal:4',
            'counted_quantity' => 'decimal:4',
            'difference' => 'decimal:4',
        ];
    }

    public function stocktake(): BelongsTo
    {
        return $this->belongsTo(InventoryStocktake::class, 'inventory_stocktake_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
