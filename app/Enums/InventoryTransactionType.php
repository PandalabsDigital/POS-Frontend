<?php

namespace App\Enums;

enum InventoryTransactionType: string
{
    case Purchase = 'purchase';
    case SaleConsumption = 'sale_consumption';
    case Wastage = 'wastage';
    case Adjustment = 'adjustment';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Return = 'return';
    case RefundReversal = 'refund_reversal';
    case OpeningStock = 'opening_stock';
    case ProductionIn = 'production_in';
    case ProductionOut = 'production_out';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Purchase',
            self::SaleConsumption => 'Sale consumption',
            self::Wastage => 'Wastage',
            self::Adjustment => 'Adjustment',
            self::TransferIn => 'Transfer in',
            self::TransferOut => 'Transfer out',
            self::Return => 'Return',
            self::RefundReversal => 'Refund reversal',
            self::OpeningStock => 'Opening stock',
            self::ProductionIn => 'Production in',
            self::ProductionOut => 'Production out',
        };
    }
}
