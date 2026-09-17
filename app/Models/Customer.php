<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'phone',
    'phone_normalized',
    'email',
    'marketing_consent',
    'marketing_consent_at',
    'source',
    'branch_name',
    'terminal_name',
    'created_by',
    'loyalty_points',
    'total_spent',
    'visits_count',
    'last_ordered_at',
])]
class Customer extends Model
{
    protected function casts(): array
    {
        return [
            'marketing_consent' => 'boolean',
            'marketing_consent_at' => 'datetime',
            'last_ordered_at' => 'datetime',
            'total_spent' => 'decimal:2',
            'loyalty_points' => 'integer',
            'visits_count' => 'integer',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    public function displayName(): string
    {
        return filled($this->name) ? $this->name : 'Guest';
    }

    public function maskedPhone(): string
    {
        $digits = $this->phone_normalized ?: static::normalizePhone((string) $this->phone);

        if (strlen($digits) < 4) {
            return $this->phone;
        }

        return substr($digits, 0, 2).str_repeat('X', max(0, strlen($digits) - 4)).substr($digits, -2);
    }
}
