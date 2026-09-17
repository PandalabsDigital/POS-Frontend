@php
    $q = $filter->query();
    $hasExtra = $filter->orderType || $filter->paymentMethod || $filter->employeeId || $filter->categoryId || $filter->productId || $filter->channel || $filter->compare === 'custom';
    $presets = [
        'today' => ['Today', 'bi-sun'],
        'yesterday' => ['Yesterday', 'bi-moon'],
        'last_7' => ['7 days', 'bi-calendar-week'],
        'this_month' => ['Month', 'bi-calendar-month'],
        'this_year' => ['Year', 'bi-calendar3'],
    ];
@endphp
<div class="intel-toolbar no-print mb-4">
    <div class="intel-preset-row">
        @foreach($presets as $value => [$label, $icon])
            <a class="intel-chip {{ $filter->preset === $value ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['preset' => $value]) }}">
                <i class="bi {{ $icon }}" aria-hidden="true"></i>
                {{ $label }}
            </a>
        @endforeach
    </div>
    <form method="GET" class="intel-date-row">
        @foreach(collect($q)->except(['preset', 'from', 'to']) as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
        <input type="hidden" name="preset" value="custom">
        <label class="intel-date">
            <i class="bi bi-calendar-event" aria-hidden="true"></i>
            <input type="date" name="from" value="{{ $filter->from->toDateString() }}">
        </label>
        <span class="text-muted">to</span>
        <label class="intel-date">
            <i class="bi bi-calendar-event-fill" aria-hidden="true"></i>
            <input type="date" name="to" value="{{ $filter->to->toDateString() }}">
        </label>
        <button class="btn btn-accent btn-sm">Go</button>
        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#intelMore">
            <i class="bi bi-funnel" aria-hidden="true"></i>
            More
        </button>
        @isset($report)
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.show', array_merge(['report' => $report, 'export' => 'csv'], $q)) }}" title="Export">
                <i class="bi bi-download" aria-hidden="true"></i>
            </a>
        @endisset
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i>
        </button>
    </form>
    <div class="collapse {{ $hasExtra ? 'show' : '' }}" id="intelMore">
        <form method="GET" class="intel-more mt-3">
            <input type="hidden" name="preset" value="{{ $filter->preset }}">
            <input type="hidden" name="from" value="{{ $filter->from->toDateString() }}">
            <input type="hidden" name="to" value="{{ $filter->to->toDateString() }}">
            <select name="order_type" class="form-select">
                <option value="">All types</option>
                @foreach(\App\Enums\OrderType::cases() as $type)
                    <option value="{{ $type->value }}" @selected($filter->orderType === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
            <select name="payment_method" class="form-select">
                <option value="">All payments</option>
                @foreach($filters['payments'] as $code => $label)
                    <option value="{{ $code }}" @selected($filter->paymentMethod === $code)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="employee_id" class="form-select">
                <option value="">All staff</option>
                @foreach($filters['employees'] as $user)
                    <option value="{{ $user->id }}" @selected((int) $filter->employeeId === $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
            <select name="channel" class="form-select">
                <option value="">All channels</option>
                <option value="pos" @selected($filter->channel === 'pos')>POS</option>
                <option value="delivery" @selected($filter->channel === 'delivery')>Delivery</option>
                <option value="online" @selected($filter->channel === 'online')>Online</option>
            </select>
            <select name="category_id" class="form-select">
                <option value="">All categories</option>
                @foreach($filters['categories'] as $category)
                    <option value="{{ $category->id }}" @selected((int) $filter->categoryId === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="product_id" class="form-select">
                <option value="">All products</option>
                @foreach($filters['products'] as $product)
                    <option value="{{ $product->id }}" @selected((int) $filter->productId === $product->id)>{{ $product->name }}</option>
                @endforeach
            </select>
            <select name="compare" class="form-select">
                <option value="previous_period" @selected($filter->compare === 'previous_period')>vs last period</option>
                <option value="previous_year" @selected($filter->compare === 'previous_year')>vs last year</option>
                <option value="same_weekday" @selected($filter->compare === 'same_weekday')>vs last week</option>
                <option value="none" @selected($filter->compare === 'none')>No compare</option>
                <option value="custom" @selected($filter->compare === 'custom')>Custom compare</option>
            </select>
            <input type="date" name="compare_from" class="form-control" value="{{ $filter->compareFrom->toDateString() }}">
            <input type="date" name="compare_to" class="form-control" value="{{ $filter->compareTo->toDateString() }}">
            <button class="btn btn-accent btn-sm">Apply</button>
            <a class="btn btn-outline-secondary btn-sm" href="{{ url()->current() }}">Clear</a>
        </form>
    </div>
</div>
