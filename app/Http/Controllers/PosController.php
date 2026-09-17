<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Condiment;
use App\Models\MenuItem;
use App\Models\TaxSetting;
use App\Services\CustomerService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(): View
    {
        return view('pos.index', [
            'categories' => Category::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'condiments' => Condiment::query()->active()->orderBy('name')->get(),
            'taxSettings' => TaxSetting::current()->load('defaultRule'),
            'paymentMethods' => config('taxation.payment_methods'),
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $query = MenuItem::query()
            ->active()
            ->with(['category', 'sizes' => fn ($query) => $query->active()])
            ->whereHas('category', fn ($builder) => $builder->active())
            ->where(function ($builder) {
                $builder->whereHas('sizes', fn ($sizes) => $sizes->active())
                    ->orWhereDoesntHave('sizes');
            });

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where('name', 'like', '%'.$search.'%');
        }

        $items = $query->orderBy('name')->get()->map(fn (MenuItem $item) => [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'base_price' => (float) $item->base_price,
            'image_url' => $item->imageUrl(),
            'category' => $item->category?->name,
            'sizes' => $item->sizes->map(fn ($size) => [
                'id' => $size->id,
                'name' => $size->name,
                'price' => (float) $size->price,
            ])->values(),
        ]);

        return response()->json(['data' => $items]);
    }

    public function quote(Request $request, OrderService $orders): JsonResponse
    {
        $data = $request->validate([
            'type' => ['nullable', 'string'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.menu_size_id' => ['nullable', 'integer', 'exists:menu_sizes,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.condiment_ids' => ['nullable', 'array'],
            'items.*.condiment_ids.*' => ['integer', 'exists:condiments,id'],
            'items.*.notes' => ['nullable', 'string', 'max:191'],
        ]);

        return response()->json($orders->quote(
            $data['items'],
            (float) ($data['discount_percent'] ?? 0),
            $data['type'] ?? null,
        ));
    }

    public function lookupCustomer(Request $request, CustomerService $customers): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $customer = $customers->findByPhone($data['phone']);

        if ($customer === null) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'name' => $customer->name,
            'phone' => $customer->phone,
        ]);
    }
}
