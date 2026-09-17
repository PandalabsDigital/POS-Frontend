<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'action',
    'inventory_item_id',
    'old_quantity',
    'new_quantity',
    'reason',
    'reference',
    'meta',
])]
class InventoryAuditLog extends Model
{
    protected function casts(): array
    {
        return [
            'old_quantity' => 'decimal:4',
            'new_quantity' => 'decimal:4',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
