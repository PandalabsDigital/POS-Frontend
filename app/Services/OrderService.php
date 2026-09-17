<?php

namespace App\Services;

use App\Enums\CustomerCaptureStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Condiment;
use App\Models\Customer;
use App\Models\MenuItem;
use App\Models\MenuSize;
use App\Models\Order;
use App\Models\Setting;
use App\Models\TaxSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private TaxEngine $taxEngine,
        private CustomerService $customers,
        private InventoryService $inventory,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $cartItems
     */
    public function create(
        User $cashier,
        OrderType $type,
        array $cartItems,
        ?string $customerNote = null,
        float $discountPercent = 0,
        ?string $paymentMethod = null,
        ?string $customerName = null,
        ?string $customerPhone = null,
        ?string $deliveryAddress = null,
        array $capture = [],
    ): Order {
        if ($cartItems === []) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one item to the cart.',
            ]);
        }

        return DB::transaction(function () use ($cashier, $type, $cartItems, $customerNote, $discountPercent, $paymentMethod, $customerName, $customerPhone, $deliveryAddress, $capture) {
            $prepared = [];
            $quoteLines = [];

            foreach ($cartItems as $index => $raw) {
                $line = $this->prepareLine($raw, $index);
                $prepared[] = $line;
                $quoteLines[] = [
                    'line_total' => $line['line_total'],
                    'menu_item' => $line['menu_item'],
                ];
            }

            $quote = $this->quoted($quoteLines, $discountPercent, $type->value);
            $settings = TaxSetting::current();
            $resolved = $this->resolveCapture($cashier, $customerName, $customerPhone, $capture);

            $order = Order::query()->create([
                'invoice_number' => $this->nextInvoiceNumber(),
                'user_id' => $cashier->id,
                'customer_id' => $resolved['customer']?->id,
                'type' => $type,
                'status' => OrderStatus::Completed,
                'subtotal' => $quote['subtotal'],
                'discount_amount' => $quote['discount'],
                'discount_percent' => $quote['discount_percent'],
                'service_charge_amount' => $quote['service_charge'],
                'taxable_amount' => $quote['taxable_amount'],
                'tax_percent' => $quote['tax_percent'],
                'tax_amount' => $quote['tax_amount'],
                'tax_label' => $quote['tax_label'],
                'tax_applicable' => $quote['tax_applicable'],
                'tax_snapshot' => $quote['snapshot'],
                'grand_total' => $quote['grand_total'],
                'payment_method' => $paymentMethod,
                'country_code' => $settings->country_code,
                'customer_note' => $customerNote,
                'customer_name' => $resolved['name'],
                'customer_phone' => $resolved['phone'],
                'customer_email' => $resolved['email'],
                'customer_capture_status' => $resolved['status']->value,
                'customer_capture_reason' => $resolved['reason'],
                'customer_capture_note' => $resolved['note'],
                'customer_capture_user_id' => $cashier->id,
                'customer_capture_at' => now(),
                'capture_source' => $resolved['source'],
                'capture_terminal' => CustomerCaptureSettings::current()['terminal_name'],
                'delivery_address' => $type === OrderType::Delivery ? $deliveryAddress : null,
                'completed_at' => now(),
            ]);

            foreach ($prepared as $index => $line) {
                $quoted = $quote['lines'][$index];
                unset($line['menu_item']);
                $order->items()->create([
                    ...$line,
                    'tax_rule_code' => $quoted['tax_rule_code'],
                    'taxable_amount' => $quoted['taxable_amount'],
                    'tax_amount' => $quoted['tax_amount'],
                    'tax_snapshot' => $quoted['rule_snapshot'],
                ]);
            }

            if ($resolved['customer'] && $resolved['status']->isCaptured()) {
                $this->customers->applyOrderStats($resolved['customer'], (float) $quote['grand_total']);
            }

            $this->customers->recordEvent(
                $cashier,
                $resolved['status'],
                $order,
                $resolved['customer'],
                $resolved['reason'],
                $resolved['note'],
                ['order_type' => $type->value],
            );

            $order->load(['items.menuItem']);
            $this->inventory->consumeOrder($order, $cashier);

            return $order->load(['items', 'cashier']);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $cartItems
     * @return array<string, mixed>
     */
    public function quote(array $cartItems, float $discountPercent = 0, ?string $orderType = null): array
    {
        $quoteLines = [];

        foreach ($cartItems as $index => $raw) {
            $line = $this->prepareLine($raw, $index);
            $quoteLines[] = [
                'line_total' => $line['line_total'],
                'menu_item' => $line['menu_item'],
            ];
        }

        return $this->quoted($quoteLines, $discountPercent, $orderType);
    }

    /**
     * @param  array<int, array<string, mixed>>  $quoteLines
     * @return array<string, mixed>
     */
    private function quoted(array $quoteLines, float $discountPercent, ?string $orderType): array
    {
        $decimals = (int) Setting::currency()['decimals'];
        $subtotal = round(array_sum(array_map(fn (array $line) => (float) $line['line_total'], $quoteLines)), $decimals);
        $percent = min(max(0, round($discountPercent, 2)), 100);
        $amount = $subtotal > 0 ? min($subtotal, round($subtotal * ($percent / 100), $decimals)) : 0.0;
        $quote = $this->taxEngine->calculateTax($quoteLines, $amount, $orderType);
        $quote['discount_percent'] = $percent;
        $quote['snapshot']['discount_percent'] = $percent;

        return $quote;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function prepareLine(array $raw, int $index): array
    {
        $menuItem = MenuItem::query()->with(['sizes', 'taxRule.components', 'category.taxRule.components'])->find($raw['menu_item_id'] ?? null);

        if (! $menuItem || ! $menuItem->is_active) {
            throw ValidationException::withMessages([
                "items.$index.menu_item_id" => 'A selected menu item is unavailable.',
            ]);
        }

        $quantity = max(1, (int) ($raw['quantity'] ?? 1));
        $size = null;

        if (! empty($raw['menu_size_id'])) {
            $size = MenuSize::query()
                ->where('menu_item_id', $menuItem->id)
                ->active()
                ->find($raw['menu_size_id']);

            if (! $size) {
                throw ValidationException::withMessages([
                    "items.$index.menu_size_id" => 'That size is not available.',
                ]);
            }
        }

        $unitPrice = $size ? (float) $size->price : (float) $menuItem->base_price;
        $condimentSnapshots = [];

        foreach ((array) ($raw['condiment_ids'] ?? []) as $condimentId) {
            $condiment = Condiment::query()->active()->find($condimentId);

            if (! $condiment) {
                continue;
            }

            $unitPrice += (float) $condiment->price;
            $condimentSnapshots[] = [
                'id' => $condiment->id,
                'name' => $condiment->name,
                'price' => (float) $condiment->price,
            ];
        }

        $lineTotal = round($unitPrice * $quantity, 2);

        return [
            'menu_item_id' => $menuItem->id,
            'menu_item' => $menuItem,
            'name' => $menuItem->name,
            'size_name' => $size?->name,
            'unit_price' => round($unitPrice, 2),
            'quantity' => $quantity,
            'condiments' => $condimentSnapshots,
            'notes' => isset($raw['notes']) ? mb_substr((string) $raw['notes'], 0, 191) : null,
            'line_total' => $lineTotal,
        ];
    }

    private function nextInvoiceNumber(): string
    {
        $date = now()->format('Ymd');
        $code = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) Setting::get('invoice_prefix', 'INV'))) ?: 'INV';
        $prefix = "{$code}-{$date}-";

        $last = Order::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('invoice_number');

        $sequence = 1;

        if ($last) {
            $sequence = ((int) substr($last, -4)) + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $capture
     * @return array{customer: ?Customer, status: CustomerCaptureStatus, name: ?string, phone: ?string, email: ?string, reason: ?string, note: ?string, source: string}
     */
    private function resolveCapture(User $cashier, ?string $customerName, ?string $customerPhone, array $capture): array
    {
        $name = $capture['customer_name'] ?? $customerName;
        $phone = $capture['customer_phone'] ?? $customerPhone;
        $email = $capture['customer_email'] ?? null;
        $reason = $capture['reason'] ?? null;
        $note = $capture['note'] ?? null;
        $source = $capture['source'] ?? 'pos';
        $statusValue = $capture['status'] ?? null;

        $status = $statusValue
            ? CustomerCaptureStatus::from($statusValue)
            : (filled($phone) ? CustomerCaptureStatus::Captured : CustomerCaptureStatus::AnonymousOrder);

        $customer = null;

        if ($status->isCaptured() && filled($phone)) {
            $customer = $this->customers->remember(
                $cashier,
                $phone,
                $name,
                is_string($email) ? $email : null,
                (bool) ($capture['marketing_consent'] ?? false),
                $source,
            );
        }

        return [
            'customer' => $customer,
            'status' => $status,
            'name' => $customer?->name ?: $name,
            'phone' => $customer?->phone ?: $phone,
            'email' => $customer?->email ?: $email,
            'reason' => $status->isCaptured() ? null : ($reason ?: $status->label()),
            'note' => $status->isCaptured() ? null : $note,
            'source' => $source,
        ];
    }

    public function refund(Order $order, User $user): Order
    {
        if ($order->status !== OrderStatus::Completed) {
            throw ValidationException::withMessages([
                'order' => 'Only completed orders can be refunded.',
            ]);
        }

        return DB::transaction(function () use ($order, $user) {
            $this->inventory->reverseOrder($order, $user);
            $order->update(['status' => OrderStatus::Refunded]);

            return $order->fresh(['items', 'cashier']);
        });
    }
}
