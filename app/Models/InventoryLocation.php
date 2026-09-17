<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code', 'is_default', 'is_active'])]
class InventoryLocation extends Model
{
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function balances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function defaultLocation(): self
    {
        $location = static::query()->where('is_default', true)->first()
            ?? static::query()->orderBy('id')->first();

        if ($location) {
            return $location;
        }

        return static::query()->create([
            'name' => 'Main Kitchen',
            'code' => 'kitchen',
            'is_default' => true,
            'is_active' => true,
        ]);
    }
}
