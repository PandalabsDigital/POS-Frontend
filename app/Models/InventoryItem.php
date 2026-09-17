<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'sku',
    'barcode',
    'inventory_category_id',
    'subcategory',
    'stock_unit_id',
    'purchase_unit_id',
    'purchase_to_stock_factor',
    'minimum_stock',
    'maximum_stock',
    'reorder_level',
    'reorder_quantity',
    'average_cost',
    'last_purchase_cost',
    'preferred_supplier_id',
    'storage_location',
    'track_expiry',
    'track_batches',
    'tax_category',
    'is_active',
    'is_finished_good',
])]
class InventoryItem extends Model
{
    protected function casts(): array
    {
        return [
            'purchase_to_stock_factor' => 'decimal:6',
            'minimum_stock' => 'decimal:4',
            'maximum_stock' => 'decimal:4',
            'reorder_level' => 'decimal:4',
            'reorder_quantity' => 'decimal:4',
            'average_cost' => 'decimal:4',
            'last_purchase_cost' => 'decimal:4',
            'track_expiry' => 'boolean',
            'track_batches' => 'boolean',
            'is_active' => 'boolean',
            'is_finished_good' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }

    public function stockUnit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'stock_unit_id');
    }

    public function purchaseUnit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'purchase_unit_id');
    }

    public function preferredSupplier(): BelongsTo
    {
        return $this->belongsTo(InventorySupplier::class, 'preferred_supplier_id');
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(InventorySupplier::class, 'inventory_item_suppliers')
            ->withPivot('last_cost')
            ->withTimestamps();
    }

    public function balances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function onHand(): float
    {
        if ($this->relationLoaded('balances')) {
            return (float) $this->balances->sum('quantity');
        }

        return (float) $this->balances()->sum('quantity');
    }

    public function stockValue(): float
    {
        return round($this->onHand() * (float) $this->average_cost, 2);
    }

    public function reorderPoint(): float
    {
        return (float) ($this->reorder_level ?? $this->minimum_stock ?? 0);
    }

    public function suggestedReorderQty(): float
    {
        if ($this->reorder_quantity) {
            return (float) $this->reorder_quantity;
        }

        $max = (float) ($this->maximum_stock ?? 0);
        $onHand = $this->onHand();

        if ($max > $onHand) {
            return round($max - $onHand, 4);
        }

        return (float) $this->minimum_stock;
    }

    public function isLowStock(): bool
    {
        return $this->onHand() <= $this->reorderPoint();
    }

    public function isOutOfStock(): bool
    {
        return $this->onHand() <= 0;
    }
}
