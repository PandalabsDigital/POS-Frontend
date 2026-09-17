<?php

namespace App\Services;

use App\Enums\CustomerCaptureStatus;
use App\Models\Customer;
use App\Models\CustomerCaptureEvent;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;

class CustomerService
{
    public function findByPhone(string $phone): ?Customer
    {
        $normalized = Customer::normalizePhone($phone);

        if (strlen($normalized) < 8) {
            return null;
        }

        return Customer::query()->where('phone_normalized', $normalized)->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function profile(Customer $customer): array
    {
        $lastVisit = $customer->last_ordered_at;

        return [
            'id' => $customer->id,
            'name' => $customer->displayName(),
            'raw_name' => $customer->name,
            'phone' => $customer->phone,
            'phone_masked' => $customer->maskedPhone(),
            'email' => $customer->email,
            'marketing_consent' => (bool) $customer->marketing_consent,
            'visits' => (int) $customer->visits_count,
            'last_visit' => $lastVisit?->toIso8601String(),
            'last_visit_human' => $lastVisit ? $lastVisit->diffForHumans() : 'New guest',
            'total_spent' => (float) $customer->total_spent,
            'total_spent_formatted' => money($customer->total_spent),
            'loyalty_points' => (int) $customer->loyalty_points,
        ];
    }

    public function remember(
        User $staff,
        string $phone,
        ?string $name = null,
        ?string $email = null,
        bool $marketingConsent = false,
        string $source = 'pos',
    ): Customer {
        $normalized = Customer::normalizePhone($phone);
        $existing = Customer::query()->where('phone_normalized', $normalized)->first();
        $settings = CustomerCaptureSettings::current();
        $now = now();

        $payload = [
            'phone' => $phone,
        ];

        if (filled($name)) {
            $payload['name'] = $name;
        } elseif ($existing === null) {
            $payload['name'] = '';
        }

        if (filled($email)) {
            $payload['email'] = $email;
        }

        if ($marketingConsent) {
            $payload['marketing_consent'] = true;
            $payload['marketing_consent_at'] = $existing?->marketing_consent_at ?? $now;
        } else {
            $payload['marketing_consent'] = false;
            $payload['marketing_consent_at'] = null;
        }

        if ($existing === null) {
            $payload['source'] = $source;
            $payload['branch_name'] = Setting::restaurantName();
            $payload['terminal_name'] = $settings['terminal_name'];
            $payload['created_by'] = $staff->id;
            $payload['phone_normalized'] = $normalized;
        }

        $customer = Customer::query()->updateOrCreate(
            ['phone_normalized' => $normalized],
            $payload,
        );

        return $customer;
    }

    public function applyOrderStats(Customer $customer, float $grandTotal): void
    {
        $customer->increment('visits_count');
        $customer->increment('total_spent', $grandTotal);
        $customer->increment('loyalty_points', (int) floor($grandTotal));
        $customer->forceFill(['last_ordered_at' => now()])->save();
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function recordEvent(
        User $staff,
        CustomerCaptureStatus $status,
        ?Order $order = null,
        ?Customer $customer = null,
        ?string $reason = null,
        ?string $note = null,
        array $meta = [],
    ): CustomerCaptureEvent {
        return CustomerCaptureEvent::query()->create([
            'order_id' => $order?->id,
            'customer_id' => $customer?->id,
            'user_id' => $staff->id,
            'status' => $status,
            'reason' => $reason,
            'note' => $note,
            'source' => 'pos',
            'meta' => $meta,
        ]);
    }

    public function lastVisitHuman(?Carbon $at): string
    {
        return $at ? $at->diffForHumans() : 'New guest';
    }
}
