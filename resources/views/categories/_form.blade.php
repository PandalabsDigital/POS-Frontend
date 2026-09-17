@php
    $category = $category ?? null;
@endphp
<div class="mb-3">
    <label class="form-label">Name</label>
    <input name="name" value="{{ old('name', $category->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" required>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label">Tax category</label>
    <select name="tax_rule_id" class="form-select">
        <option value="" @selected(old('tax_rule_id', $category->tax_rule_id ?? '') === '' || old('tax_rule_id', $category->tax_rule_id ?? '') === null)>Restaurant</option>
        @foreach(($taxRules ?? []) as $rule)
            <option value="{{ $rule->id }}" @selected((string) old('tax_rule_id', $category->tax_rule_id ?? '') === (string) $rule->id)>
                {{ $rule->classificationLabel() }} · {{ $rule->tax_name }} {{ $rule->formatRate() }}% ({{ $rule->tax_type }})
            </option>
        @endforeach
    </select>
    <div class="form-text">Applies to items in this category unless an item has its own tax rule.</div>
</div>
<div class="form-check mb-4">
    <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))>
    <label class="form-check-label">Enabled</label>
</div>
