<?php

namespace App\Http\Requests;

use App\Enums\OrderType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'restaurant_name' => ['required', 'string', 'max:120'],
            'currency_code' => ['required', 'string', Rule::in(array_keys(config('currencies')))],
            'address' => ['nullable', 'string', 'max:191'],
            'city' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'website' => ['nullable', 'string', 'max:120'],
            'opening_hours' => ['nullable', 'string', 'max:120'],
            'receipt_footer' => ['nullable', 'string', 'max:191'],
            'invoice_prefix' => ['nullable', 'string', 'max:12'],
            'timezone' => ['required', 'string', Rule::in(array_keys(config('restaurant.timezones')))],
            'default_order_type' => ['required', Rule::enum(OrderType::class)],
            'show_cashier_on_receipt' => ['required', 'boolean'],
            'restaurant_logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'remove_restaurant_logo' => ['sometimes', 'boolean'],
            'inventory_enabled' => ['required', 'boolean'],
            'inventory_auto_deduct' => ['required', 'boolean'],
            'inventory_allow_negative' => ['required', 'boolean'],
            'inventory_auto_disable_menu' => ['required', 'boolean'],
            'inventory_enable_batches' => ['required', 'boolean'],
            'inventory_enable_expiry' => ['required', 'boolean'],
            'inventory_low_stock_alerts' => ['required', 'boolean'],
            'inventory_food_cost_target' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $booleans = [
            'show_cashier_on_receipt',
            'remove_restaurant_logo',
            'inventory_enabled',
            'inventory_auto_deduct',
            'inventory_allow_negative',
            'inventory_auto_disable_menu',
            'inventory_enable_batches',
            'inventory_enable_expiry',
            'inventory_low_stock_alerts',
        ];
        $merged = [];
        foreach ($booleans as $key) {
            $merged[$key] = $this->boolean($key);
        }
        $this->merge($merged);
    }

    public function messages(): array
    {
        return [
            'restaurant_logo.mimes' => 'Use a JPG, PNG, WEBP, or GIF logo.',
            'restaurant_logo.max' => 'The logo must be 2 MB or smaller.',
        ];
    }
}
