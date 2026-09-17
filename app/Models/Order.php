<?php

namespace App\Models;

use App\Enums\CustomerCaptureStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'invoice_number',
    'user_id',
    'type',
    'status',
    'subtotal',
    'discount_amount',
    'discount_percent',
    'service_charge_amount',
    'taxable_amount',
    'tax_percent',
    'tax_amount',
    'tax_label',
    'tax_applicable',
    'tax_snapshot',
    'grand_total',
    'payment_method',
    'country_code',
    'customer_note',
    'customer_id',
    'customer_name',
    'customer_phone',
    'customer_email',
    'customer_capture_status',
    'customer_capture_reason',
    'customer_capture_note',
    'customer_capture_user_id',
    'customer_capture_at',
    'capture_source',
    'capture_terminal',
    'delivery_address',
    'completed_at',
    'inventory_posted_at',
    'inventory_reversed_at',
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'type' => OrderType::class,
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'service_charge_amount' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'tax_applicable' => 'boolean',
            'tax_snapshot' => 'array',
            'completed_at' => 'datetime',
            'customer_capture_status' => CustomerCaptureStatus::class,
            'customer_capture_at' => 'datetime',
            'inventory_posted_at' => 'datetime',
            'inventory_reversed_at' => 'datetime',
        ];
    }

    public function discountRate(): float
    {
        if ((float) $this->discount_percent > 0) {
            return (float) $this->discount_percent;
        }

        $subtotal = (float) $this->subtotal;

        return $subtotal > 0 ? round(((float) $this->discount_amount / $subtotal) * 100, 2) : 0.0;
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function captureStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_capture_user_id');
    }
}
