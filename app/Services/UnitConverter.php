<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryUnit;
use Illuminate\Validation\ValidationException;

class UnitConverter
{
    public function toStockQuantity(InventoryItem $item, float $quantity, InventoryUnit $from): float
    {
        $stock = $item->stockUnit;

        if ($from->id === $stock->id) {
            return round($quantity, 4);
        }

        if ($from->id === $item->purchase_unit_id && (float) $item->purchase_to_stock_factor > 0) {
            return round($quantity * (float) $item->purchase_to_stock_factor, 4);
        }

        if ($from->dimension === $stock->dimension && $from->dimension !== 'other') {
            $base = $quantity * (float) $from->to_base;
            $stockQty = $base / max((float) $stock->to_base, 0.00000001);

            return round($stockQty, 4);
        }

        throw ValidationException::withMessages([
            'unit' => "Cannot convert {$from->code} to {$stock->code} for {$item->name}. Set a purchase conversion factor.",
        ]);
    }
}
