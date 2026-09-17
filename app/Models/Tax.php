<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'percent', 'is_enabled'])]
class Tax extends Model
{
    protected function casts(): array
    {
        return [
            'percent' => 'decimal:2',
            'is_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            ['name' => 'Sales Tax', 'percent' => 8.00, 'is_enabled' => true]
        );
    }
}
