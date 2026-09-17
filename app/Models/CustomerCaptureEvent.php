<?php

namespace App\Models;

use App\Enums\CustomerCaptureStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'customer_id',
    'user_id',
    'status',
    'reason',
    'note',
    'source',
    'meta',
])]
class CustomerCaptureEvent extends Model
{
    protected function casts(): array
    {
        return [
            'status' => CustomerCaptureStatus::class,
            'meta' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
