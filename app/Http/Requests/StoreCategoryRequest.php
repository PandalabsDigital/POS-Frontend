<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'is_active' => ['sometimes', 'boolean'],
            'tax_rule_id' => ['nullable', 'integer', 'exists:tax_rules,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('tax_rule_id') === '' || $this->input('tax_rule_id') === '0') {
            $this->merge(['tax_rule_id' => null]);
        }
    }
}
