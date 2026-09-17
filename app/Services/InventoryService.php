<?php

namespace App\Services;

use App\Enums\InventoryTransactionType;
use App\Models\InventoryAuditLog;
use App\Models\InventoryBalance;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryRecipe;
use App\Models\InventoryTransaction;
use App\Models\InventoryUnit;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(private UnitConverter $units) {}

    public function getStockBalance(InventoryItem $item, ?InventoryLocation $location = null): float
    {
        $query = InventoryBalance::query()->where('inventory_item_id', $item->id);

        if ($location) {
            $query->where('inventory_location_id', $location->id);
        }

        return (float) $query->sum('quantity');
    }

    public function calculateInventoryValue(): float
    {
        return round((float) InventoryBalance::query()
            ->join('inventory_items', 'inventory_items.id', '=', 'inventory_balances.inventory_item_id')
            ->selectRaw('SUM(inventory_balances.quantity * inventory_items.average_cost) as value')
            ->value('value'), 2);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function receiveStock(
        InventoryItem $item,
        float $quantity,
        InventoryUnit $unit,
        float $unitCost,
        User $user,
        InventoryLocation $location,
        InventoryTransactionType $type = InventoryTransactionType::Purchase,
        ?string $reason = null,
        array $meta = [],
        ?string $batchNumber = null,
        ?string $expiresAt = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $referenceNumber = null,
        ?string $notes = null,
    ): InventoryTransaction {
        $stockQty = $this->units->toStockQuantity($item, $quantity, $unit);
        $stockCost = $this->costPerStockUnit($item, $quantity, $unit, $unitCost);

        return $this->move(
            $item,
            $location,
            $stockQty,
            0,
            $stockCost,
            $type,
            $user,
            $reason,
            $meta,
            $batchNumber,
            $expiresAt,
            $referenceType,
            $referenceId,
            $referenceNumber,
            $notes,
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function consumeStock(
        InventoryItem $item,
        float $quantity,
        InventoryUnit $unit,
        User $user,
        InventoryLocation $location,
        InventoryTransactionType $type = InventoryTransactionType::SaleConsumption,
        ?string $reason = null,
        array $meta = [],
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $referenceNumber = null,
        ?string $notes = null,
    ): void {
        $stockQty = $this->units->toStockQuantity($item, $quantity, $unit);
        $this->assertEnough($item, $location, $stockQty);

        $this->consumeFromBatches($item, $location, $stockQty, $type, $user, $reason, $meta, $referenceType, $referenceId, $referenceNumber, $notes);
    }

    public function adjustStock(
        InventoryItem $item,
        float $actualStockQty,
        User $user,
        InventoryLocation $location,
        string $reason,
        ?string $notes = null,
    ): ?InventoryTransaction {
        $balance = $this->balance($item, $location);
        $current = (float) $balance->quantity;
        $diff = round($actualStockQty - $current, 4);

        if (abs($diff) < 0.0001) {
            return null;
        }

        $in = $diff > 0 ? $diff : 0;
        $out = $diff < 0 ? abs($diff) : 0;

        if ($out > 0) {
            $this->assertEnough($item, $location, $out);
        }

        return $this->move(
            $item,
            $location,
            $in,
            $out,
            (float) $item->average_cost,
            $reason === 'opening_balance' ? InventoryTransactionType::OpeningStock : InventoryTransactionType::Adjustment,
            $user,
            $reason,
            ['system_qty' => $current, 'actual_qty' => $actualStockQty],
            null,
            null,
            'adjustment',
            null,
            null,
            $notes,
        );
    }

    public function recordWastage(
        InventoryItem $item,
        float $quantity,
        InventoryUnit $unit,
        User $user,
        InventoryLocation $location,
        string $reason,
        ?string $notes = null,
    ): InventoryTransaction {
        $stockQty = $this->units->toStockQuantity($item, $quantity, $unit);
        $this->assertEnough($item, $location, $stockQty);

        $txns = $this->consumeFromBatches(
            $item,
            $location,
            $stockQty,
            InventoryTransactionType::Wastage,
            $user,
            $reason,
            [],
            'wastage',
            null,
            null,
            $notes,
        );

        return $txns[0];
    }

    /**
     * @param  list<array{item: InventoryItem, quantity: float, unit: InventoryUnit}>  $lines
     */
    public function transferStock(
        InventoryLocation $from,
        InventoryLocation $to,
        array $lines,
        User $user,
        ?string $notes = null,
        ?int $transferId = null,
    ): void {
        if ($from->id === $to->id) {
            throw ValidationException::withMessages([
                'to_location_id' => 'Choose a different destination location.',
            ]);
        }

        foreach ($lines as $line) {
            $stockQty = $this->units->toStockQuantity($line['item'], $line['quantity'], $line['unit']);
            $this->assertEnough($line['item'], $from, $stockQty);
            $cost = (float) $line['item']->average_cost;

            $this->consumeFromBatches(
                $line['item'],
                $from,
                $stockQty,
                InventoryTransactionType::TransferOut,
                $user,
                'transfer',
                [],
                'transfer',
                $transferId,
                null,
                $notes,
            );

            $this->move(
                $line['item'],
                $to,
                $stockQty,
                0,
                $cost,
                InventoryTransactionType::TransferIn,
                $user,
                'transfer',
                [],
                null,
                null,
                'transfer',
                $transferId,
                null,
                $notes,
            );
        }
    }

    public function consumeOrder(Order $order, User $user): void
    {
        $settings = InventorySettings::current();

        if (! $settings['enabled'] || ! $settings['auto_deduct'] || $order->inventory_posted_at) {
            return;
        }

        $location = InventoryLocation::defaultLocation();
        $order->loadMissing(['items.menuItem']);

        DB::transaction(function () use ($order, $user, $location) {
            foreach ($order->items as $line) {
                $recipe = InventoryRecipe::query()
                    ->where('menu_item_id', $line->menu_item_id)
                    ->where('type', 'sale')
                    ->where('is_active', true)
                    ->with('ingredients.item.stockUnit', 'ingredients.unit')
                    ->first();

                if (! $recipe) {
                    continue;
                }

                $portions = max(1, (int) $line->quantity) / max((float) $recipe->yield_quantity, 0.0001);

                foreach ($recipe->ingredients as $ingredient) {
                    $qty = $ingredient->effectiveQuantity() * $portions;
                    $this->consumeStock(
                        $ingredient->item,
                        $qty,
                        $ingredient->unit,
                        $user,
                        $location,
                        InventoryTransactionType::SaleConsumption,
                        'pos_sale',
                        [
                            'recipe_id' => $recipe->id,
                            'recipe_version' => $recipe->version,
                            'menu_item_id' => $line->menu_item_id,
                            'order_item_id' => $line->id,
                        ],
                        'order',
                        $order->id,
                        $order->invoice_number,
                    );
                }
            }

            $order->forceFill(['inventory_posted_at' => now()])->save();
            $this->syncMenuAvailability();
        });
    }

    public function reverseOrder(Order $order, User $user): void
    {
        if (! $order->inventory_posted_at || $order->inventory_reversed_at) {
            return;
        }

        $this->reverseReference('order', $order->id, $user, InventoryTransactionType::RefundReversal);
        $order->forceFill(['inventory_reversed_at' => now()])->save();
        $this->syncMenuAvailability();
    }

    public function reverseReference(string $referenceType, int $referenceId, User $user, InventoryTransactionType $type = InventoryTransactionType::RefundReversal): void
    {
        $original = InventoryTransaction::query()
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->whereNotIn('type', [
                InventoryTransactionType::RefundReversal->value,
                InventoryTransactionType::Return->value,
            ])
            ->orderBy('id')
            ->get();

        foreach ($original as $txn) {
            $item = $txn->item;
            $location = $txn->location;
            $in = (float) $txn->quantity_out;
            $out = (float) $txn->quantity_in;

            if ($in <= 0 && $out <= 0) {
                continue;
            }

            $this->move(
                $item,
                $location,
                $in,
                $out,
                (float) $txn->unit_cost,
                $type,
                $user,
                'reversal',
                ['reversed_transaction_id' => $txn->id],
                null,
                null,
                $referenceType,
                $referenceId,
                $txn->reference_number,
                'Reversal of #'.$txn->id,
            );
        }
    }

    public function produce(InventoryRecipe $recipe, float $batches, User $user, InventoryLocation $location): void
    {
        if (! $recipe->isProduction() || ! $recipe->outputItem) {
            throw ValidationException::withMessages([
                'recipe' => 'This recipe does not produce a stock item.',
            ]);
        }

        DB::transaction(function () use ($recipe, $batches, $user, $location) {
            $recipe->load('ingredients.item.stockUnit', 'ingredients.unit', 'outputItem.stockUnit');
            $output = $recipe->outputItem;
            $yield = (float) $recipe->yield_quantity * $batches;
            $ingredientCost = 0.0;

            foreach ($recipe->ingredients as $ingredient) {
                $qty = $ingredient->effectiveQuantity() * $batches;
                $stockQty = $this->units->toStockQuantity($ingredient->item, $qty, $ingredient->unit);
                $ingredientCost += $stockQty * (float) $ingredient->item->average_cost;
                $this->consumeStock(
                    $ingredient->item,
                    $qty,
                    $ingredient->unit,
                    $user,
                    $location,
                    InventoryTransactionType::ProductionOut,
                    'production',
                    ['recipe_id' => $recipe->id],
                    'recipe',
                    $recipe->id,
                );
            }

            $unitCost = $yield > 0 ? $ingredientCost / $yield : 0;
            $this->receiveStock(
                $output,
                $yield,
                $recipe->yieldUnit ?? $output->stockUnit,
                $unitCost,
                $user,
                $location,
                InventoryTransactionType::ProductionIn,
                'production',
                ['recipe_id' => $recipe->id],
                null,
                null,
                'recipe',
                $recipe->id,
            );
        });
    }

    public function syncMenuAvailability(): void
    {
        if (! InventorySettings::current()['auto_disable_menu']) {
            return;
        }

        $recipes = InventoryRecipe::query()
            ->where('type', 'sale')
            ->where('is_active', true)
            ->whereNotNull('menu_item_id')
            ->with('ingredients.item')
            ->get();

        foreach ($recipes as $recipe) {
            $unavailable = false;

            foreach ($recipe->ingredients as $ingredient) {
                if ($ingredient->is_essential && $ingredient->item->onHand() <= 0) {
                    $unavailable = true;
                    break;
                }
            }

            if ($unavailable && $recipe->menuItem) {
                $recipe->menuItem->update(['is_active' => false]);
            }
        }
    }

    /**
     * @return list<InventoryTransaction>
     */
    private function consumeFromBatches(
        InventoryItem $item,
        InventoryLocation $location,
        float $stockQty,
        InventoryTransactionType $type,
        User $user,
        ?string $reason,
        array $meta,
        ?string $referenceType,
        ?int $referenceId,
        ?string $referenceNumber,
        ?string $notes,
    ): array {
        $settings = InventorySettings::current();
        $remaining = $stockQty;
        $txns = [];

        if ($settings['enable_batches'] && $item->track_batches) {
            $batches = InventoryBatch::query()
                ->where('inventory_item_id', $item->id)
                ->where('inventory_location_id', $location->id)
                ->where('quantity', '>', 0)
                ->orderByRaw('expires_at is null')
                ->orderBy('expires_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min((float) $batch->quantity, $remaining);
                $batch->decrement('quantity', $take);
                $txns[] = $this->move(
                    $item,
                    $location,
                    0,
                    $take,
                    (float) $item->average_cost,
                    $type,
                    $user,
                    $reason,
                    $meta,
                    $batch->batch_number,
                    optional($batch->expires_at)->toDateString(),
                    $referenceType,
                    $referenceId,
                    $referenceNumber,
                    $notes,
                    $batch->id,
                    false,
                );
                $remaining = round($remaining - $take, 4);
            }
        }

        if ($remaining > 0.0001) {
            $txns[] = $this->move(
                $item,
                $location,
                0,
                $remaining,
                (float) $item->average_cost,
                $type,
                $user,
                $reason,
                $meta,
                null,
                null,
                $referenceType,
                $referenceId,
                $referenceNumber,
                $notes,
            );
        }

        return $txns;
    }

    private function move(
        InventoryItem $item,
        InventoryLocation $location,
        float $qtyIn,
        float $qtyOut,
        float $unitCost,
        InventoryTransactionType $type,
        User $user,
        ?string $reason,
        array $meta,
        ?string $batchNumber,
        ?string $expiresAt,
        ?string $referenceType,
        ?int $referenceId,
        ?string $referenceNumber,
        ?string $notes,
        ?int $batchId = null,
        bool $updateBatches = true,
    ): InventoryTransaction {
        $balance = $this->balance($item, $location);
        $oldQty = (float) $balance->quantity;
        $newQty = round($oldQty + $qtyIn - $qtyOut, 4);

        if ($qtyIn > 0) {
            $oldValue = $oldQty * (float) $item->average_cost;
            $addValue = $qtyIn * $unitCost;
            $avg = $newQty > 0 ? ($oldValue + $addValue) / $newQty : $unitCost;
            $item->average_cost = round($avg, 4);
            if (in_array($type, [InventoryTransactionType::Purchase, InventoryTransactionType::OpeningStock], true)) {
                $item->last_purchase_cost = round($unitCost, 4);
            }
            $item->save();
            $balance->average_cost = $item->average_cost;
        }

        $balance->quantity = $newQty;
        $balance->save();

        if ($updateBatches && $qtyIn > 0 && InventorySettings::current()['enable_batches'] && ($item->track_batches || $item->track_expiry)) {
            $batch = InventoryBatch::query()->create([
                'inventory_item_id' => $item->id,
                'inventory_location_id' => $location->id,
                'batch_number' => $batchNumber,
                'expires_at' => $expiresAt,
                'quantity' => $qtyIn,
                'unit_cost' => $unitCost,
            ]);
            $batchId = $batch->id;
        }

        $txn = InventoryTransaction::query()->create([
            'inventory_item_id' => $item->id,
            'inventory_location_id' => $location->id,
            'inventory_batch_id' => $batchId,
            'type' => $type,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reference_number' => $referenceNumber,
            'quantity_in' => $qtyIn,
            'quantity_out' => $qtyOut,
            'balance_after' => $newQty,
            'unit_cost' => $unitCost,
            'total_value' => round(($qtyIn + $qtyOut) * $unitCost, 4),
            'reason' => $reason,
            'notes' => $notes,
            'meta' => $meta ?: null,
            'user_id' => $user->id,
            'occurred_at' => now(),
        ]);

        InventoryAuditLog::query()->create([
            'user_id' => $user->id,
            'action' => $type->value,
            'inventory_item_id' => $item->id,
            'old_quantity' => $oldQty,
            'new_quantity' => $newQty,
            'reason' => $reason,
            'reference' => $referenceNumber ?: ($referenceType ? $referenceType.'#'.$referenceId : null),
            'meta' => $meta ?: null,
        ]);

        return $txn;
    }

    private function balance(InventoryItem $item, InventoryLocation $location): InventoryBalance
    {
        $balance = InventoryBalance::query()
            ->where('inventory_item_id', $item->id)
            ->where('inventory_location_id', $location->id)
            ->lockForUpdate()
            ->first();

        if ($balance) {
            return $balance;
        }

        return InventoryBalance::query()->create([
            'inventory_item_id' => $item->id,
            'inventory_location_id' => $location->id,
            'quantity' => 0,
            'average_cost' => (float) ($item->average_cost ?? 0),
        ]);
    }

    private function assertEnough(InventoryItem $item, InventoryLocation $location, float $needed): void
    {
        if (InventorySettings::current()['allow_negative']) {
            return;
        }

        $available = $this->getStockBalance($item, $location);

        if ($needed - $available > 0.0001) {
            throw ValidationException::withMessages([
                'inventory' => "Insufficient inventory for {$item->name}. Available: {$available} {$item->stockUnit->code}. Required: {$needed} {$item->stockUnit->code}.",
            ]);
        }
    }

    private function costPerStockUnit(InventoryItem $item, float $qty, InventoryUnit $unit, float $unitCost): float
    {
        $stockQty = $this->units->toStockQuantity($item, $qty, $unit);

        return $stockQty > 0 ? round($unitCost * $qty / $stockQty, 4) : $unitCost;
    }
}
