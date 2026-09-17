<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'inventory_item_id',
    'inventory_location_id',
    'unit_id',
    'quantity',
    'stock_quantity',
    'cost',
    'reason',
    'notes',
    'user_id',
    'occurred_at',
])]
class InventoryWastage extends Model
{
    protected $table = 'inventory_wastage';

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'stock_quantity' => 'decimal:4',
            'cost' => 'decimal:4',
            'occurred_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'unit_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
