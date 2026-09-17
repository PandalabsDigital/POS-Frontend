<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tax_rule_id', 'name', 'rate', 'sort_order'])]
class TaxRuleComponent extends Model
{
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:3',
        ];
    }

    public function taxRule(): BelongsTo
    {
        return $this->belongsTo(TaxRule::class);
    }
}
