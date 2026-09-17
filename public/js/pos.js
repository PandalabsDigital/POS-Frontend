(() => {
    const state = {
        products: [],
        cart: [],
        type: document.getElementById('pos-root')?.dataset.defaultOrderType || 'dine_in',
        quote: null,
        selected: null,
    };

    const els = {
        grid: document.getElementById('product-grid'),
        search: document.getElementById('product-search'),
        cart: document.getElementById('cart-list'),
        subtotal: document.getElementById('subtotal'),
        discount: document.getElementById('discount'),
        discountInput: document.getElementById('discount-percent'),
        serviceCharge: document.getElementById('service-charge'),
        taxable: document.getElementById('taxable'),
        tax: document.getElementById('tax'),
        taxBreakdown: document.getElementById('tax-breakdown'),
        total: document.getElementById('grand-total'),
        payment: document.getElementById('payment-method'),
        checkout: document.getElementById('checkout-btn'),
        addItem: document.getElementById('add-item-btn'),
        qty: document.getElementById('modal-qty'),
        notes: document.getElementById('modal-notes'),
        sizes: document.getElementById('modal-sizes'),
        condiments: document.getElementById('modal-condiments'),
        modalTitle: document.getElementById('itemModalLabel'),
        customerName: document.getElementById('customer-name'),
        customerPhone: document.getElementById('customer-phone'),
        customerDeclined: document.getElementById('customer-declined'),
        declineReason: document.getElementById('customer-decline-reason'),
        declineReasonWrap: document.getElementById('decline-reason-wrap'),
        deliveryAddress: document.getElementById('delivery-address'),
        deliveryAddressWrap: document.getElementById('delivery-address-wrap'),
    };

    const root = document.getElementById('pos-root');
    const money = (n) => {
        const symbol = root?.dataset.currencySymbol || '$';
        const decimals = Number(root?.dataset.currencyDecimals || 2);
        return `${symbol}${Number(n).toFixed(decimals)}`;
    };
    const modal = () => bootstrap.Modal.getOrCreateInstance(document.getElementById('itemModal'));

    function setPane(pane) {
        root?.classList.toggle('show-cart', pane === 'cart');
        document.querySelectorAll('.pos-tab').forEach((tab) => {
            tab.classList.toggle('active', tab.dataset.pane === pane);
        });
        if (pane === 'cart') {
            document.getElementById('pos-cart-scroll')?.scrollTo({ top: 0 });
        }
    }

    function updateCartBadge() {
        const count = state.cart.reduce((sum, line) => sum + line.quantity, 0);
        const badge = document.getElementById('cart-badge');
        if (badge) badge.textContent = String(count);
    }

    async function loadProducts(categoryId = '', q = '') {
        const params = new URLSearchParams();
        if (categoryId) params.set('category_id', categoryId);
        if (q) params.set('q', q);
        const response = await window.posFetch(`/pos/products?${params.toString()}`);
        const json = await response.json();
        state.products = json.data || [];
        renderProducts();
    }

    function renderProducts() {
        els.grid.innerHTML = state.products.map((item) => {
            const img = item.image_url
                ? `<img src="${item.image_url}" alt="${item.name}">`
                : `<div class="product-fallback">${item.name.charAt(0)}</div>`;
            const price = item.sizes?.[0]?.price ?? item.base_price;
            return `<button class="product-tile" data-product="${item.id}">
                ${img}
                <div class="p-2">
                    <div class="fw-semibold">${item.name}</div>
                    <div class="small text-muted">${money(price)}</div>
                </div>
            </button>`;
        }).join('') || '<p class="text-muted">No products found.</p>';
    }

    function openProduct(id) {
        const item = state.products.find((p) => p.id === Number(id));
        if (!item) return;
        state.selected = item;
        els.modalTitle.textContent = item.name;
        els.qty.value = 1;
        els.notes.value = '';
        els.sizes.innerHTML = (item.sizes || []).map((size, index) => `
            <label class="chip me-2 mb-2">
                <input type="radio" name="size" value="${size.id}" class="d-none" ${index === 0 ? 'checked' : ''}>
                ${size.name} · ${money(size.price)}
            </label>
        `).join('');
        document.getElementById('size-wrap')?.classList.toggle('d-none', !(item.sizes || []).length);
        els.condiments.querySelectorAll('input').forEach((input) => { input.checked = false; });
        highlightChips(els.sizes);
        modal().show();
    }

    function highlightChips(container) {
        container.querySelectorAll('label').forEach((label) => {
            const input = label.querySelector('input');
            label.classList.toggle('active', Boolean(input?.checked));
            input?.addEventListener('change', () => highlightChips(container));
        });
    }

    function selectedSize() {
        const id = Number(document.querySelector('input[name="size"]:checked')?.value || 0);
        return (state.selected?.sizes || []).find((size) => size.id === id) || null;
    }

    function selectedCondiments() {
        return [...document.querySelectorAll('#modal-condiments input:checked')].map((input) => ({
            id: Number(input.value),
            name: input.dataset.name,
            price: Number(input.dataset.price),
        }));
    }

    function addToCart() {
        if (!state.selected) return;
        const size = selectedSize();
        const condiments = selectedCondiments();
        const quantity = Math.max(1, Number(els.qty.value || 1));
        const unit = (size?.price ?? state.selected.base_price) + condiments.reduce((sum, c) => sum + c.price, 0);
        state.cart.push({
            uid: crypto.randomUUID(),
            menu_item_id: state.selected.id,
            name: state.selected.name,
            menu_size_id: size?.id || null,
            size_name: size?.name || '',
            quantity,
            condiment_ids: condiments.map((c) => c.id),
            condiment_names: condiments.map((c) => c.name),
            notes: els.notes.value.trim(),
            unit_price: unit,
        });
        modal().hide();
        renderCart();
        if (window.matchMedia('(max-width: 991.98px)').matches) {
            setPane('cart');
        }
    }

    function renderCart() {
        els.cart.innerHTML = state.cart.map((line) => `
            <div class="cart-line py-3">
                <div class="d-flex justify-content-between gap-2">
                    <div>
                        <div class="fw-semibold">${line.name}</div>
                        <div class="small text-muted">${line.size_name || ''} ${line.condiment_names.length ? '· ' + line.condiment_names.join(', ') : ''}</div>
                        ${line.notes ? `<div class="small fst-italic text-muted">${line.notes}</div>` : ''}
                    </div>
                    <div class="text-end">
                        <div>${money(line.unit_price * line.quantity)}</div>
                        <button class="btn btn-sm btn-link text-danger p-0" data-remove="${line.uid}">Remove</button>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 mt-2">
                    <button class="btn btn-sm btn-outline-secondary" data-qty="${line.uid}" data-delta="-1">−</button>
                    <span>${line.quantity}</span>
                    <button class="btn btn-sm btn-outline-secondary" data-qty="${line.uid}" data-delta="1">+</button>
                </div>
            </div>
        `).join('') || '<p class="text-muted mt-4">Cart is empty.</p>';

        updateCartBadge();
        scheduleQuote();
    }

    let quoteTimer;
    function scheduleQuote() {
        clearTimeout(quoteTimer);
        quoteTimer = setTimeout(fetchQuote, 120);
    }

    function cartPayload() {
        return {
            type: state.type,
            discount_percent: Number(els.discountInput?.value || 0),
            items: state.cart.map((line) => ({
                menu_item_id: line.menu_item_id,
                menu_size_id: line.menu_size_id,
                quantity: line.quantity,
                condiment_ids: line.condiment_ids,
                notes: line.notes,
            })),
        };
    }

    function renderTotals(quote) {
        const empty = !state.cart.length;
        els.subtotal.textContent = money(empty ? 0 : quote?.subtotal || 0);
        if (els.discount) {
            const percent = empty ? 0 : Number(els.discountInput?.value || quote?.discount_percent || 0);
            els.discount.textContent = percent ? `${percent}%` : '0%';
        }
        if (els.serviceCharge) els.serviceCharge.textContent = money(empty ? 0 : quote?.service_charge || 0);
        if (els.taxable) els.taxable.textContent = money(empty ? 0 : quote?.taxable_amount || 0);
        els.total.textContent = money(empty ? 0 : quote?.grand_total || 0);

        if (empty || !quote) {
            els.tax.textContent = money(0);
            if (els.taxBreakdown) els.taxBreakdown.innerHTML = '';
            return;
        }

        if (quote.tax_display === 'not_applicable') {
            els.tax.textContent = 'Not Applicable';
            if (els.taxBreakdown) els.taxBreakdown.innerHTML = '';
            return;
        }

        els.tax.textContent = money(quote.tax_amount || 0);
        if (els.taxBreakdown) {
            els.taxBreakdown.innerHTML = (quote.tax_breakdown || []).map((row) => (
                `<div class="d-flex justify-content-between"><span>${row.name}</span><span>${money(row.amount)}</span></div>`
            )).join('');
        }
    }

    async function fetchQuote() {
        if (!state.cart.length) {
            state.quote = null;
            renderTotals(null);
            return;
        }
        const response = await window.posFetch(root?.dataset.quoteUrl || '/pos/quote', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(cartPayload()),
        });
        if (!response.ok) {
            return;
        }
        state.quote = await response.json();
        renderTotals(state.quote);
    }

    async function checkout() {
        if (!state.cart.length) return;
        const declined = Boolean(els.customerDeclined?.checked);
        const customerName = (els.customerName?.value || '').trim();
        const customerPhone = (els.customerPhone?.value || '').trim();
        const declineReason = (els.declineReason?.value || '').trim();
        if (declined) {
            if (declineReason.length < 10) {
                alert('Write a valid reason why the customer declined.');
                setPane('cart');
                els.declineReason?.focus();
                return;
            }
        } else if (!customerName || !customerPhone) {
            alert('Enter the customer name and phone number, or mark Customer declined.');
            setPane('cart');
            els.customerName?.focus();
            return;
        }
        const deliveryAddress = (els.deliveryAddress?.value || '').trim();
        if (state.type === 'delivery' && !deliveryAddress) {
            alert('Enter the delivery address so the rider knows where to go.');
            setPane('cart');
            els.deliveryAddress?.focus();
            return;
        }
        els.checkout.disabled = true;
        const payload = {
            ...cartPayload(),
            payment_method: els.payment?.value || 'cash',
            customer_declined: declined,
            customer_name: declined ? null : customerName,
            customer_phone: declined ? null : customerPhone,
            customer_capture_reason: declined ? declineReason : null,
            delivery_address: state.type === 'delivery' ? deliveryAddress : null,
        };
        const response = await window.posFetch('/orders', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        els.checkout.disabled = false;
        if (!response.ok) {
            alert(data.message || Object.values(data.errors || {}).flat().join('\n') || 'Could not complete order.');
            return;
        }
        state.cart = [];
        if (els.discountInput) els.discountInput.value = 0;
        if (els.customerName) els.customerName.value = '';
        if (els.customerPhone) els.customerPhone.value = '';
        if (els.customerDeclined) els.customerDeclined.checked = false;
        if (els.declineReason) els.declineReason.value = '';
        if (els.deliveryAddress) els.deliveryAddress.value = '';
        syncDeclinedFields();
        renderCart();
        window.open(data.receipt_url, '_blank', 'width=420,height=720');
    }

    async function lookupCustomer() {
        if (els.customerDeclined?.checked) return;
        const phone = (els.customerPhone?.value || '').trim();
        if (phone.length < 8) return;
        const url = new URL(root?.dataset.customerLookupUrl || '/pos/customers', window.location.origin);
        url.searchParams.set('phone', phone);
        const response = await window.posFetch(url.toString());
        if (!response.ok) return;
        const data = await response.json();
        if (data.found && data.name && els.customerName && !els.customerName.value.trim()) {
            els.customerName.value = data.name;
        }
    }

    function syncDeclinedFields() {
        const declined = Boolean(els.customerDeclined?.checked);
        els.declineReasonWrap?.classList.toggle('d-none', !declined);
        if (els.customerName) els.customerName.disabled = declined;
        if (els.customerPhone) els.customerPhone.disabled = declined;
    }

    document.getElementById('category-chips')?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-category]');
        if (!button) return;
        document.querySelectorAll('[data-category]').forEach((el) => el.classList.remove('active'));
        button.classList.add('active');
        loadProducts(button.dataset.category, els.search.value.trim());
    });

    els.search?.addEventListener('input', () => {
        const active = document.querySelector('[data-category].active')?.dataset.category || '';
        loadProducts(active, els.search.value.trim());
    });

    els.grid?.addEventListener('click', (event) => {
        const tile = event.target.closest('[data-product]');
        if (tile) openProduct(tile.dataset.product);
    });

    els.cart?.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-remove]');
        const qty = event.target.closest('[data-qty]');
        if (remove) {
            state.cart = state.cart.filter((line) => line.uid !== remove.dataset.remove);
            renderCart();
        }
        if (qty) {
            const line = state.cart.find((item) => item.uid === qty.dataset.qty);
            if (!line) return;
            line.quantity = Math.max(1, line.quantity + Number(qty.dataset.delta));
            renderCart();
        }
    });

    function syncDeliveryAddressField() {
        const isDelivery = state.type === 'delivery';
        els.deliveryAddressWrap?.classList.toggle('d-none', !isDelivery);
        if (els.deliveryAddress) {
            els.deliveryAddress.required = isDelivery;
        }
    }

    document.getElementById('order-types')?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-type]');
        if (!button) return;
        state.type = button.dataset.type;
        document.querySelectorAll('[data-type]').forEach((el) => el.classList.toggle('active', el === button));
        syncDeliveryAddressField();
        scheduleQuote();
    });

    els.discountInput?.addEventListener('input', scheduleQuote);
    els.customerPhone?.addEventListener('blur', lookupCustomer);
    els.customerDeclined?.addEventListener('change', syncDeclinedFields);
    els.addItem?.addEventListener('click', addToCart);
    els.checkout?.addEventListener('click', checkout);
    els.condiments?.addEventListener('change', () => highlightChips(els.condiments));
    document.querySelectorAll('.pos-tab').forEach((tab) => {
        tab.addEventListener('click', () => setPane(tab.dataset.pane));
    });

    document.querySelectorAll('#order-types [data-type]').forEach((el) => {
        el.classList.toggle('active', el.dataset.type === state.type);
    });
    syncDeliveryAddressField();
    syncDeclinedFields();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => loadProducts().then(renderCart));
    } else {
        loadProducts().then(renderCart);
    }
})();
