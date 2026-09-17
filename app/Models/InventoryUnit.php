<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'dimension', 'to_base', 'is_custom'])]
class InventoryUnit extends Model
{
    protected function casts(): array
    {
        return [
            'to_base' => 'decimal:8',
            'is_custom' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'stock_unit_id');
    }
}
