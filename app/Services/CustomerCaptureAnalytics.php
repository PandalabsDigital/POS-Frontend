<?php

namespace App\Services;

use App\Enums\CustomerCaptureStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerCaptureAnalytics
{
    /**
     * @return array<string, mixed>
     */
    public function summary(Carbon $from, Carbon $to): array
    {
        $orders = Order::query()
            ->where('status', OrderStatus::Completed)
            ->whereBetween('completed_at', [$from, $to]);

        $total = (clone $orders)->count();
        $identified = (clone $orders)->where('customer_capture_status', CustomerCaptureStatus::Captured)->count();
        $declined = (clone $orders)->where('customer_capture_status', CustomerCaptureStatus::CustomerDeclined)->count();
        $anonymous = (clone $orders)->where('customer_capture_status', CustomerCaptureStatus::AnonymousOrder)->count();
        $unable = (clone $orders)->where('customer_capture_status', CustomerCaptureStatus::StaffUnableToCapture)->count();
        $other = (clone $orders)->where('customer_capture_status', CustomerCaptureStatus::Other)->count();
        $unspecified = $total - $identified - $declined - $anonymous - $unable - $other;

        return [
            'total_orders' => $total,
            'identified' => $identified,
            'declined' => $declined,
            'anonymous' => $anonymous,
            'staff_unable' => $unable,
            'other' => $other,
            'unspecified' => max(0, $unspecified),
            'capture_rate' => $total > 0 ? round(($identified / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function byStaff(Carbon $from, Carbon $to): Collection
    {
        $rows = Order::query()
            ->select([
                'user_id',
                DB::raw('COUNT(*) as orders_count'),
                DB::raw("SUM(CASE WHEN customer_capture_status = 'captured' THEN 1 ELSE 0 END) as captured_count"),
                DB::raw("SUM(CASE WHEN customer_capture_status = 'customer_declined' THEN 1 ELSE 0 END) as declined_count"),
                DB::raw("SUM(CASE WHEN customer_capture_status = 'staff_unable_to_capture' THEN 1 ELSE 0 END) as unable_count"),
                DB::raw("SUM(CASE WHEN customer_capture_status = 'anonymous_order' THEN 1 ELSE 0 END) as anonymous_count"),
            ])
            ->where('status', OrderStatus::Completed)
            ->whereBetween('completed_at', [$from, $to])
            ->groupBy('user_id')
            ->get();

        $users = User::query()->whereIn('id', $rows->pluck('user_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($users) {
            $orders = (int) $row->orders_count;
            $captured = (int) $row->captured_count;

            return (object) [
                'staff' => $users[$row->user_id]->name ?? 'Staff #'.$row->user_id,
                'orders' => $orders,
                'captured' => $captured,
                'declined' => (int) $row->declined_count,
                'unable' => (int) $row->unable_count,
                'anonymous' => (int) $row->anonymous_count,
                'rate' => $orders > 0 ? round(($captured / $orders) * 100, 1) : 0.0,
            ];
        })->sortByDesc('orders')->values();
    }
}
