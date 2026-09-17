<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'inventory_purchase_id',
    'inventory_item_id',
    'unit_id',
    'quantity',
    'stock_quantity',
    'unit_cost',
    'tax_amount',
    'discount_amount',
    'line_total',
    'batch_number',
    'expires_at',
])]
class InventoryPurchaseItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'stock_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'line_total' => 'decimal:4',
            'expires_at' => 'date',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(InventoryPurchase::class, 'inventory_purchase_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'unit_id');
    }
}
