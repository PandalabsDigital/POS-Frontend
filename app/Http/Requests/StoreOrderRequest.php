<?php

namespace App\Http\Requests;

use App\Enums\OrderType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $declined = $this->boolean('customer_declined');

        return [
            'type' => ['required', Rule::enum(OrderType::class)],
            'customer_note' => ['nullable', 'string', 'max:191'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_method' => ['nullable', 'string', Rule::in(array_keys(config('taxation.payment_methods')))],
            'customer_declined' => ['sometimes', 'boolean'],
            'customer_name' => [$declined ? 'nullable' : 'required', 'string', 'max:80'],
            'customer_phone' => [$declined ? 'nullable' : 'required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{8,20}$/'],
            'customer_capture_reason' => [$declined ? 'required' : 'nullable', 'string', 'min:10', 'max:191'],
            'delivery_address' => [
                Rule::requiredIf($this->input('type') === OrderType::Delivery->value),
                'nullable',
                'string',
                'max:255',
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.menu_size_id' => ['nullable', 'integer', 'exists:menu_sizes,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.condiment_ids' => ['nullable', 'array'],
            'items.*.condiment_ids.*' => ['integer', 'exists:condiments,id'],
            'items.*.notes' => ['nullable', 'string', 'max:191'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_capture_reason.required' => 'Write a valid reason why the customer declined.',
            'customer_capture_reason.min' => 'Write a valid reason why the customer declined.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_declined' => $this->boolean('customer_declined'),
        ]);

        if ($this->boolean('customer_declined')) {
            $this->merge([
                'customer_name' => $this->filled('customer_name') ? $this->input('customer_name') : null,
                'customer_phone' => $this->filled('customer_phone') ? $this->input('customer_phone') : null,
                'customer_capture_reason' => trim((string) $this->input('customer_capture_reason', '')),
            ]);
        }
    }
}
