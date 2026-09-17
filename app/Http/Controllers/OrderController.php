<?php

namespace App\Http\Controllers;

use App\Enums\CustomerCaptureStatus;
use App\Enums\OrderType;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, OrderService $orders): JsonResponse
    {
        $data = $request->validated();
        $declined = (bool) ($data['customer_declined'] ?? false);

        $order = $orders->create(
            $request->user(),
            OrderType::from($data['type']),
            $data['items'],
            $data['customer_note'] ?? null,
            (float) ($data['discount_percent'] ?? 0),
            $data['payment_method'] ?? null,
            $declined ? null : ($data['customer_name'] ?? null),
            $declined ? null : ($data['customer_phone'] ?? null),
            $data['delivery_address'] ?? null,
            [
                'status' => $declined
                    ? CustomerCaptureStatus::CustomerDeclined->value
                    : CustomerCaptureStatus::Captured->value,
                'reason' => $declined ? $data['customer_capture_reason'] : null,
                'customer_name' => $declined ? null : ($data['customer_name'] ?? null),
                'customer_phone' => $declined ? null : ($data['customer_phone'] ?? null),
                'source' => 'pos',
            ],
        );

        return response()->json([
            'message' => 'Order completed.',
            'order_id' => $order->id,
            'invoice_number' => $order->invoice_number,
            'receipt_url' => route('orders.receipt', $order),
        ]);
    }

    public function receipt(Order $order): View
    {
        $order->load(['items', 'cashier']);

        return view('orders.receipt', compact('order'));
    }

    public function refund(Order $order, OrderService $orders): RedirectResponse
    {
        $orders->refund($order, request()->user());

        return back()->with('success', 'Order refunded. Inventory restored with a reversal transaction.');
    }
}
