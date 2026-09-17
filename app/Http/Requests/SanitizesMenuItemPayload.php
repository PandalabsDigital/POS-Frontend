<?php

namespace App\Http\Requests;

trait SanitizesMenuItemPayload
{
    protected function prepareMenuItemPayload(): void
    {
        $sizes = collect($this->input('sizes', []))
            ->filter(function ($size): bool {
                return filled($size['name'] ?? null) || filled($size['price'] ?? null);
            })
            ->values()
            ->all();

        $payload = ['sizes' => $sizes];

        if ($this->input('tax_rule_id') === '' || $this->input('tax_rule_id') === '0') {
            $payload['tax_rule_id'] = null;
        }

        $this->merge($payload);
    }

    protected function validateActiveSizes($validator): void
    {
        $sizes = collect($this->input('sizes', []));

        if ($sizes->isEmpty()) {
            return;
        }

        $hasActiveSize = $sizes->contains(function ($size): bool {
            return filter_var($size['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);
        });

        if (! $hasActiveSize) {
            $validator->errors()->add('sizes', 'Turn on at least one size, or leave all size fields blank to sell a single portion at the base price.');
        }
    }
}
