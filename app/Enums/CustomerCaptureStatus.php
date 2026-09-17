<?php

namespace App\Enums;

enum CustomerCaptureStatus: string
{
    case Captured = 'captured';
    case CustomerDeclined = 'customer_declined';
    case AnonymousOrder = 'anonymous_order';
    case StaffUnableToCapture = 'staff_unable_to_capture';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Captured => 'Captured',
            self::CustomerDeclined => 'Customer declined',
            self::AnonymousOrder => 'Anonymous order',
            self::StaffUnableToCapture => 'Staff unable to capture',
            self::Other => 'Other',
        };
    }

    public function isCaptured(): bool
    {
        return $this === self::Captured;
    }

    /**
     * @return list<self>
     */
    public static function skipCases(): array
    {
        return [
            self::CustomerDeclined,
            self::AnonymousOrder,
            self::StaffUnableToCapture,
            self::Other,
        ];
    }
}
