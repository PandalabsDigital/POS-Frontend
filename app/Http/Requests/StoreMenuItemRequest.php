<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuItemRequest extends FormRequest
{
    use SanitizesMenuItemPayload;

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
            'sizes' => ['nullable', 'array'],
            'sizes.*.name' => ['required', 'string', 'max:40'],
            'sizes.*.price' => ['required', 'numeric', 'min:0'],
            'sizes.*.is_active' => ['sometimes', 'boolean'],
            'tax_rule_id' => ['nullable', 'integer', 'exists:tax_rules,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(fn ($validator) => $this->validateActiveSizes($validator));
    }

    protected function prepareForValidation(): void
    {
        $this->prepareMenuItemPayload();
    }
}
