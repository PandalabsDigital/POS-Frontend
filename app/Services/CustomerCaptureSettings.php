<?php

namespace App\Services;

use App\Enums\CustomerCaptureStatus;
use App\Enums\OrderType;
use App\Models\Setting;

class CustomerCaptureSettings
{
    /**
     * @return array{
     *     enabled: bool,
     *     require_dine_in: bool,
     *     require_takeaway: bool,
     *     require_delivery: bool,
     *     allow_anonymous: bool,
     *     require_skip_reason: bool,
     *     require_other_note: bool,
     *     minimum_info: string,
     *     enable_analytics: bool,
     *     terminal_name: string,
     *     skip_reasons: array<string, array{label: string, status: string}>
     * }
     */
    public static function current(): array
    {
        $defaults = config('customer_capture.defaults');

        return [
            'enabled' => Setting::bool('capture_enabled', (bool) $defaults['enabled']),
            'require_dine_in' => Setting::bool('capture_require_dine_in', (bool) $defaults['require_dine_in']),
            'require_takeaway' => Setting::bool('capture_require_takeaway', (bool) $defaults['require_takeaway']),
            'require_delivery' => Setting::bool('capture_require_delivery', (bool) $defaults['require_delivery']),
            'allow_anonymous' => Setting::bool('capture_allow_anonymous', (bool) $defaults['allow_anonymous']),
            'require_skip_reason' => Setting::bool('capture_require_skip_reason', (bool) $defaults['require_skip_reason']),
            'require_other_note' => Setting::bool('capture_require_other_note', (bool) $defaults['require_other_note']),
            'minimum_info' => (string) Setting::get('capture_minimum_info', $defaults['minimum_info']),
            'enable_analytics' => Setting::bool('capture_enable_analytics', (bool) $defaults['enable_analytics']),
            'terminal_name' => (string) Setting::get('capture_terminal_name', $defaults['terminal_name']),
            'skip_reasons' => config('customer_capture.skip_reasons'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function payloadFromCheckout(array $data): array
    {
        $skipping = (bool) ($data['continue_without_customer'] ?? false);
        $source = $data['capture_source'] ?? 'pos';

        if ($skipping) {
            $reason = $data['customer_capture_reason'] ?? 'anonymous_order';

            return [
                'status' => static::statusFromReason($reason)->value,
                'reason' => $reason,
                'note' => $data['customer_capture_note'] ?? null,
                'customer_name' => null,
                'customer_phone' => null,
                'customer_email' => null,
                'marketing_consent' => false,
                'source' => $source,
            ];
        }

        $name = $data['customer_name'] ?? null;
        $phone = $data['customer_phone'] ?? null;
        $captured = filled($phone) || filled($name);

        if (! $captured) {
            return [
                'status' => CustomerCaptureStatus::AnonymousOrder->value,
                'reason' => 'anonymous_order',
                'note' => null,
                'customer_name' => null,
                'customer_phone' => null,
                'customer_email' => null,
                'marketing_consent' => false,
                'source' => $source,
            ];
        }

        return [
            'status' => CustomerCaptureStatus::Captured->value,
            'reason' => null,
            'note' => null,
            'customer_name' => $name,
            'customer_phone' => $phone,
            'customer_email' => $data['customer_email'] ?? null,
            'marketing_consent' => (bool) ($data['marketing_consent'] ?? false),
            'source' => $source,
        ];
    }

    public static function identificationRequiredFor(OrderType $type): bool
    {
        $settings = static::current();

        return match ($type) {
            OrderType::DineIn => $settings['require_dine_in'],
            OrderType::Takeaway => $settings['require_takeaway'],
            OrderType::Delivery => $settings['require_delivery'],
        };
    }

    public static function statusFromReason(string $reason): CustomerCaptureStatus
    {
        $code = config('customer_capture.skip_reasons.'.$reason.'.status', 'other');

        return CustomerCaptureStatus::from($code);
    }
}
