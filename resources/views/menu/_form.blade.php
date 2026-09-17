@php
    $item = $item ?? null;
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" value="{{ old('name', $item->name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Category</label>
        <select name="category_id" class="form-select" required>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $item->category_id ?? '') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Base price</label>
        <input type="number" step="0.01" min="0" name="base_price" class="form-control" value="{{ old('base_price', $item->base_price ?? '0.00') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Image</label>
        <input type="file" name="image" accept="image/*" class="form-control">
    </div>
    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2">{{ old('description', $item->description ?? '') }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label">Tax category</label>
        <select name="tax_rule_id" class="form-select">
            <option value="" @selected(old('tax_rule_id', $item->tax_rule_id ?? '') === '' || old('tax_rule_id', $item->tax_rule_id ?? '') === null)>
                Restaurant
            </option>
            @foreach(($taxRules ?? []) as $rule)
                <option value="{{ $rule->id }}" @selected((string) old('tax_rule_id', $item->tax_rule_id ?? '') === (string) $rule->id)>
                    {{ $rule->classificationLabel() }} · {{ $rule->tax_name }} {{ $rule->formatRate() }}% ({{ $rule->tax_type }})
                </option>
            @endforeach
        </select>
        <div class="form-text">
            Default is the restaurant tax rule
            @if(! empty($restaurantTaxRule))
                ({{ $restaurantTaxRule->tax_name }} {{ $restaurantTaxRule->formatRate() }}% · {{ $restaurantTaxRule->tax_type }})
            @endif.
            Choose another rule only to override it for this item.
        </div>
    </div>
    <div class="col-12">
        <label class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true))>
            <span class="form-check-label">Active</span>
        </label>
    </div>
</div>
<hr>
<h2 class="h6 mb-1">Sizes (optional)</h2>
<p class="small text-muted mb-3">Leave these blank if the item is a single portion. The POS will use the base price.</p>
@php
    $defaultSizes = old('sizes');
    if (! is_array($defaultSizes)) {
        $defaultSizes = $item?->sizes?->map(fn ($s) => [
            'name' => $s->name,
            'price' => $s->price,
            'is_active' => $s->is_active,
        ])->all();
    }
    if (! is_array($defaultSizes) || $defaultSizes === []) {
        $defaultSizes = [
            ['name' => '', 'price' => '', 'is_active' => true],
            ['name' => '', 'price' => '', 'is_active' => true],
            ['name' => '', 'price' => '', 'is_active' => true],
        ];
    }
@endphp
@foreach($defaultSizes as $index => $size)
    <div class="row g-2 mb-2 align-items-center">
        <div class="col-md-5"><input name="sizes[{{ $index }}][name]" class="form-control" value="{{ $size['name'] }}" placeholder="Size name"></div>
        <div class="col-md-4"><input type="number" step="0.01" min="0" name="sizes[{{ $index }}][price]" class="form-control" value="{{ $size['price'] }}" placeholder="Price"></div>
        <div class="col-md-3">
            <input type="hidden" name="sizes[{{ $index }}][is_active]" value="0">
            <label class="form-check mb-0">
                <input class="form-check-input" type="checkbox" name="sizes[{{ $index }}][is_active]" value="1" @checked(filter_var($size['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN))>
                <span class="form-check-label">Active</span>
            </label>
        </div>
    </div>
@endforeach
@error('sizes')<div class="text-danger small">{{ $message }}</div>@enderror
