<?php

namespace App\Models;

use App\Enums\InventoryTransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'inventory_item_id',
    'inventory_location_id',
    'inventory_batch_id',
    'type',
    'reference_type',
    'reference_id',
    'reference_number',
    'quantity_in',
    'quantity_out',
    'balance_after',
    'unit_cost',
    'total_value',
    'reason',
    'notes',
    'meta',
    'user_id',
    'occurred_at',
])]
class InventoryTransaction extends Model
{
    protected function casts(): array
    {
        return [
            'type' => InventoryTransactionType::class,
            'quantity_in' => 'decimal:4',
            'quantity_out' => 'decimal:4',
            'balance_after' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_value' => 'decimal:4',
            'meta' => 'array',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
