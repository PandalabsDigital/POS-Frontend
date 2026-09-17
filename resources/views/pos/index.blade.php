<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ff6a00">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="icon" href="{{ asset('images/logo-icon.jpg') }}">
    <title>POS · {{ $restaurantName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body class="pos-shell" id="pos-root"
    data-quote-url="{{ url('/pos/quote') }}"
    data-customer-lookup-url="{{ url('/pos/customers') }}"
    data-currency-symbol="{{ $currency['symbol'] }}"
    data-currency-decimals="{{ $currency['decimals'] }}"
    data-default-order-type="{{ $defaultOrderType ?? 'dine_in' }}">
<div class="row g-0 pos-row">
    <div class="col-lg-8 pos-left p-3 p-lg-4" id="pos-menu-pane">
        <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
            <div class="d-flex align-items-center gap-2 min-w-0">
                @include('partials.logo')
                <div class="min-w-0">
                    <div class="fw-bold text-truncate">{{ $restaurantName }}</div>
                    <div class="small text-muted text-truncate">{{ auth()->user()->name }} · {{ auth()->user()->role->label() }} · {{ $currency['code'] }}</div>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap justify-content-end">
                @if(auth()->user()->isAdmin())
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('dashboard', [], false) }}">
                        <i class="bi bi-house-door-fill" aria-hidden="true"></i>
                        Dashboard
                    </a>
                @endif
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('recipes.index', [], false) }}">
                    <i class="bi bi-youtube" aria-hidden="true"></i>
                    Recipes
                </a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    Log out
                </button></form>
            </div>
        </div>
        <div id="category-chips" class="d-flex flex-wrap gap-2 mb-3 pos-chips">
            <button class="chip active" data-category="">All</button>
            @foreach($categories as $category)
                <button class="chip" data-category="{{ $category->id }}">{{ $category->name }}</button>
            @endforeach
        </div>
        <input id="product-search" class="form-control mb-3" placeholder="Search menu..." inputmode="search">
        <div class="flex-grow-1 overflow-auto">
            <div id="product-grid" class="pos-grid"></div>
        </div>
    </div>
    <div class="col-lg-4 pos-right p-3 p-lg-4" id="pos-cart-pane">
        <div class="pos-cart-header">
            <h2 class="h5 mb-2">Current order</h2>
            <div id="order-types" class="d-flex flex-wrap gap-2">
                <button class="chip active" data-type="dine_in"><i class="bi bi-shop" aria-hidden="true"></i> Dine-In</button>
                <button class="chip" data-type="takeaway"><i class="bi bi-bag" aria-hidden="true"></i> Takeaway</button>
                <button class="chip" data-type="delivery"><i class="bi bi-truck" aria-hidden="true"></i> Delivery</button>
            </div>
        </div>
        <div class="pos-cart-scroll" id="pos-cart-scroll">
            <div id="cart-list"></div>
            <div class="pos-customer my-3">
                <div class="small fw-semibold mb-2"><i class="bi bi-person-fill" aria-hidden="true"></i> Customer</div>
                <label class="form-label small text-muted mb-1" for="customer-name">Name</label>
                <input id="customer-name" class="form-control form-control-sm mb-2" autocomplete="name" maxlength="80" placeholder="Guest name">
                <label class="form-label small text-muted mb-1" for="customer-phone">Phone</label>
                <input id="customer-phone" class="form-control form-control-sm mb-2" type="tel" autocomplete="tel" maxlength="20" placeholder="Phone number" inputmode="tel">
                <label class="d-flex align-items-center gap-2 small mb-2">
                    <input id="customer-declined" type="checkbox" class="form-check-input">
                    <span>Customer declined</span>
                </label>
                <div id="decline-reason-wrap" class="d-none">
                    <label class="form-label small text-muted mb-1" for="customer-decline-reason">Reason</label>
                    <textarea id="customer-decline-reason" class="form-control form-control-sm" rows="2" maxlength="191" placeholder="Write why the guest declined"></textarea>
                </div>
                <div id="delivery-address-wrap" class="d-none mt-2">
                    <label class="form-label small text-muted mb-1" for="delivery-address">Address</label>
                    <textarea id="delivery-address" class="form-control form-control-sm" rows="2" maxlength="255" placeholder="Area, building, landmark" autocomplete="street-address"></textarea>
                </div>
            </div>
            <div class="pos-totals">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="small text-muted mb-0" for="discount-percent">Discount %</label>
                    <div class="input-group input-group-sm" style="max-width: 120px">
                        <input id="discount-percent" type="number" min="0" max="100" step="0.1" value="0" class="form-control">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="small text-muted mb-0" for="payment-method">Payment</label>
                    <select id="payment-method" class="form-select form-select-sm" style="max-width: 160px">
                        @foreach($paymentMethods as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex justify-content-between"><span>Subtotal</span><span id="subtotal">{{ money(0) }}</span></div>
                <div class="d-flex justify-content-between"><span>Discount</span><span id="discount">0%</span></div>
                <div class="d-flex justify-content-between pos-tax-extra"><span>Service Charge</span><span id="service-charge">{{ money(0) }}</span></div>
                <div class="d-flex justify-content-between pos-tax-extra"><span>Taxable Amount</span><span id="taxable">{{ money(0) }}</span></div>
                <div id="tax-breakdown" class="small text-muted pos-tax-extra"></div>
                <div class="d-flex justify-content-between"><span>Tax</span><span id="tax">{{ money(0) }}</span></div>
            </div>
        </div>
        <div class="pos-cart-footer">
            <div class="d-flex justify-content-between fs-4 fw-bold mb-2"><span>Total</span><span id="grand-total">{{ money(0) }}</span></div>
            <button id="checkout-btn" class="btn btn-accent w-100 py-3">Complete order & print</button>
        </div>
    </div>
</div>

<nav class="pos-mobile-nav d-lg-none" aria-label="POS views">
    <button type="button" class="pos-tab active" data-pane="menu">
        <i class="bi bi-grid-fill" aria-hidden="true"></i>
        <span>Menu</span>
    </button>
    <button type="button" class="pos-tab" data-pane="cart">
        <i class="bi bi-cart-fill" aria-hidden="true"></i>
        <span>Cart</span>
        <span id="cart-badge" class="cart-badge">0</span>
    </button>
</nav>

<div class="modal fade" id="itemModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="itemModalLabel">Item</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3" id="size-wrap">
                    <div class="small text-muted mb-2">Size</div>
                    <div id="modal-sizes"></div>
                </div>
                <div class="mb-3">
                    <div class="small text-muted mb-2">Add Ons</div>
                    <div id="modal-condiments">
                        @foreach($condiments as $condiment)
                            <label class="chip me-2 mb-2">
                                <input type="checkbox" class="d-none" value="{{ $condiment->id }}" data-name="{{ $condiment->name }}" data-price="{{ $condiment->price }}">
                                {{ $condiment->name }} · {{ money($condiment->price) }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Quantity</label>
                    <input id="modal-qty" type="number" min="1" value="1" class="form-control form-control-lg" inputmode="numeric">
                </div>
                <div>
                    <label class="form-label">Item notes</label>
                    <input id="modal-notes" class="form-control" placeholder="No onions, extra crispy...">
                </div>
            </div>
            <div class="modal-footer">
                <button id="add-item-btn" class="btn btn-accent w-100 py-3">Add to cart</button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script src="{{ asset('js/pos.js') }}"></script>
</body>
</html>
