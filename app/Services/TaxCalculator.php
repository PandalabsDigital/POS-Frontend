<?php

namespace App\Services;

use App\Models\Tax;

class TaxCalculator
{
    public function current(): Tax
    {
        return Tax::current();
    }

    /**
     * @return array{subtotal: float, tax_percent: float, tax_amount: float, grand_total: float}
     */
    public function totals(float $subtotal): array
    {
        $tax = $this->current();
        $percent = $tax->is_enabled ? (float) $tax->percent : 0.0;
        $taxAmount = round($subtotal * ($percent / 100), 2);
        $grandTotal = round($subtotal + $taxAmount, 2);

        return [
            'subtotal' => round($subtotal, 2),
            'tax_percent' => $percent,
            'tax_amount' => $taxAmount,
            'grand_total' => $grandTotal,
        ];
    }
}
