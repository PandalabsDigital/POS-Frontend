<?php

namespace App\Services\Intelligence;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportFilter
{
    public function __construct(
        public Carbon $from,
        public Carbon $to,
        public string $preset = 'custom',
        public string $compare = 'previous_period',
        public Carbon $compareFrom,
        public Carbon $compareTo,
        public ?string $orderType = null,
        public ?string $paymentMethod = null,
        public ?int $employeeId = null,
        public ?int $categoryId = null,
        public ?int $productId = null,
        public ?string $channel = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $preset = (string) $request->query('preset', '');
        [$from, $to] = self::presetRange($preset, $request->query('from'), $request->query('to'));
        $compare = (string) $request->query('compare', 'previous_period');
        [$compareFrom, $compareTo] = self::comparisonRange($from, $to, $compare, $request->query('compare_from'), $request->query('compare_to'));

        return new self(
            from: $from,
            to: $to,
            preset: $preset !== '' ? $preset : 'custom',
            compare: $compare,
            compareFrom: $compareFrom,
            compareTo: $compareTo,
            orderType: self::nullableString($request->query('order_type')),
            paymentMethod: self::nullableString($request->query('payment_method')),
            employeeId: $request->filled('employee_id') ? $request->integer('employee_id') : null,
            categoryId: $request->filled('category_id') ? $request->integer('category_id') : null,
            productId: $request->filled('product_id') ? $request->integer('product_id') : null,
            channel: self::nullableString($request->query('channel')),
        );
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function presetRange(string $preset, ?string $from, ?string $to): array
    {
        $today = Carbon::today();

        [$start, $end] = match ($preset) {
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            'yesterday' => [$today->copy()->subDay()->startOfDay(), $today->copy()->subDay()->endOfDay()],
            'last_7' => [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay()],
            'this_week' => [$today->copy()->startOfWeek()->startOfDay(), $today->copy()->endOfWeek()->endOfDay()],
            'last_week' => [$today->copy()->subWeek()->startOfWeek()->startOfDay(), $today->copy()->subWeek()->endOfWeek()->endOfDay()],
            'this_month' => [$today->copy()->startOfMonth()->startOfDay(), $today->copy()->endOfMonth()->endOfDay()],
            'last_month' => [$today->copy()->subMonthNoOverflow()->startOfMonth()->startOfDay(), $today->copy()->subMonthNoOverflow()->endOfMonth()->endOfDay()],
            'last_3_months' => [$today->copy()->subMonthsNoOverflow(2)->startOfMonth()->startOfDay(), $today->copy()->endOfDay()],
            'last_6_months' => [$today->copy()->subMonthsNoOverflow(5)->startOfMonth()->startOfDay(), $today->copy()->endOfDay()],
            'this_year' => [$today->copy()->startOfYear()->startOfDay(), $today->copy()->endOfDay()],
            'last_year' => [$today->copy()->subYear()->startOfYear()->startOfDay(), $today->copy()->subYear()->endOfYear()->endOfDay()],
            default => [
                $from ? Carbon::parse($from)->startOfDay() : $today->copy()->startOfMonth()->startOfDay(),
                $to ? Carbon::parse($to)->endOfDay() : $today->copy()->endOfDay(),
            ],
        };

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function comparisonRange(Carbon $from, Carbon $to, string $mode, ?string $customFrom, ?string $customTo): array
    {
        $days = $from->diffInDays($to) + 1;

        return match ($mode) {
            'none' => [$from->copy(), $to->copy()],
            'previous_year' => [$from->copy()->subYear(), $to->copy()->subYear()],
            'same_weekday' => [$from->copy()->subWeek(), $to->copy()->subWeek()],
            'custom' => [
                $customFrom ? Carbon::parse($customFrom)->startOfDay() : $from->copy()->subDays($days),
                $customTo ? Carbon::parse($customTo)->endOfDay() : $to->copy()->subDays($days),
            ],
            default => [$from->copy()->subDays($days)->startOfDay(), $from->copy()->subDay()->endOfDay()],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function presets(): array
    {
        return [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'last_7' => 'Last 7 days',
            'this_week' => 'This week',
            'last_week' => 'Last week',
            'this_month' => 'This month',
            'last_month' => 'Last month',
            'last_3_months' => 'Last 3 months',
            'last_6_months' => 'Last 6 months',
            'this_year' => 'This year',
            'last_year' => 'Last year',
            'custom' => 'Custom',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return array_filter([
            'preset' => $this->preset,
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
            'compare' => $this->compare,
            'compare_from' => $this->compareFrom->toDateString(),
            'compare_to' => $this->compareTo->toDateString(),
            'order_type' => $this->orderType,
            'payment_method' => $this->paymentMethod,
            'employee_id' => $this->employeeId,
            'category_id' => $this->categoryId,
            'product_id' => $this->productId,
            'channel' => $this->channel,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }
}
