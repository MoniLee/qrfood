(function () {
    'use strict';

    var el = document.getElementById('srms-pos-initial');
    /** @type {{ menu: Array<Record<string, unknown>>, gstRate: number }} */
    var initial = el ? JSON.parse(el.textContent || '{}') : { menu: [], gstRate: 18 };
    var menuItems = /** @type {Array<any>} */ (initial.menu || []);
    var gstRate = typeof initial.gstRate === 'number' ? initial.gstRate : 18;

    var searchEl = document.getElementById('menuSearch');
    var chipsEl = document.getElementById('categoryChips');
    var gridEl = document.getElementById('menuGrid');
    var orderType = document.getElementById('orderType');
    var wrapTable = document.getElementById('wrapTable');
    var wrapDelivery = document.getElementById('wrapDelivery');
    var tableId = document.getElementById('tableId');
    var custName = document.getElementById('custName');
    var custPhone = document.getElementById('custPhone');
    var custAddr = document.getElementById('custAddr');
    var cartLines = document.getElementById('cartLines');
    var cartEmpty = document.getElementById('cartEmpty');
    var cartSummary = document.getElementById('cartSummary');
    var sumSub = document.getElementById('sumSub');
    var sumGst = document.getElementById('sumGst');
    var sumDelivery = document.getElementById('sumDelivery');
    var rowDeliveryFee = document.getElementById('rowDeliveryFee');
    var sumTotal = document.getElementById('sumTotal');
    var btnPlace = document.getElementById('btnPlace');

    /** @type {Record<number, number>} */
    var cart = {};
    /** @type {string|null} */
    var activeCat = null;

    function catsFromMenu() {
        var m = new Map();
        menuItems.forEach(function (it) {
            m.set(it.category_id, String(it.category_name || ''));
        });
        return Array.from(m.entries()).sort(function (a, b) {
            return String(a[1]).localeCompare(String(b[1]));
        });
    }

    function renderChips() {
        if (!chipsEl) return;
        chipsEl.innerHTML = '';
        var all = document.createElement('button');
        all.type = 'button';
        all.className = 'chip is-active';
        all.textContent = 'All';
        all.addEventListener('click', function () {
            activeCat = null;
            chipsEl.querySelectorAll('.chip').forEach(function (c) {
                c.classList.remove('is-active');
            });
            all.classList.add('is-active');
            renderGrid();
        });
        chipsEl.appendChild(all);
        catsFromMenu().forEach(function (_a) {
            var id = _a[0];
            var name = _a[1];
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'chip';
            b.textContent = name;
            b.dataset.cat = String(id);
            b.addEventListener('click', function () {
                activeCat = String(id);
                chipsEl.querySelectorAll('.chip').forEach(function (c) {
                    c.classList.remove('is-active');
                });
                b.classList.add('is-active');
                renderGrid();
            });
            chipsEl.appendChild(b);
        });
    }

    function filterItems() {
        var q = (searchEl && searchEl.value ? searchEl.value : '').toLowerCase().trim();
        return menuItems.filter(function (it) {
            if (activeCat !== null && String(it.category_id) !== String(activeCat)) return false;
            if (!q) return true;
            var n = String(it.name || '').toLowerCase();
            var d = String(it.description || '').toLowerCase();
            return n.indexOf(q) !== -1 || d.indexOf(q) !== -1;
        });
    }

    function renderGrid() {
        if (!gridEl) return;
        var list = filterItems();
        gridEl.innerHTML = '';
        if (list.length === 0) {
            gridEl.innerHTML = '<p class="text-muted">No items match your search.</p>';
            return;
        }
        list.forEach(function (it) {
            var id = Number(it.id);
            var qty = cart[id] || 0;
            var card = document.createElement('article');
            card.className = 'menu-card';
            var imgSrc = window.SRMS.resolveMenuImageUrl ? window.SRMS.resolveMenuImageUrl(it) : '';
            var wrap = document.createElement('div');
            var hasImage = !!imgSrc;
            wrap.className = 'menu-card-img-wrapper' + (hasImage ? ' is-loading' : '');
            var visual;
            if (hasImage) {
                visual = document.createElement('img');
                visual.alt = String(it.name || 'Dish');
                visual.className = 'menu-card-img';
                visual.loading = 'lazy';
                visual.addEventListener('load', function () {
                    visual.classList.add('is-loaded');
                    wrap.classList.remove('is-loading');
                });
                visual.addEventListener('error', function () {
                    visual.classList.add('is-error');
                    wrap.classList.remove('is-loading');
                    wrap.classList.add('is-error');
                });
                visual.src = imgSrc;
            } else {
                visual = document.createElement('div');
                visual.className = 'menu-card-placeholder';
                visual.textContent = 'Velvet Plate';
            }
            wrap.appendChild(visual);
            var body = document.createElement('div');
            body.className = 'menu-card-body';
            body.innerHTML =
                '<h3 class="menu-card-title">' +
                escapeHtml(String(it.name)) +
                '</h3>' +
                '<p class="menu-card-meta">' +
                escapeHtml(String(it.category_name || '')) +
                '</p>';
            if (it.description) {
                var p = document.createElement('p');
                p.className = 'menu-card-meta';
                p.textContent = String(it.description);
                body.appendChild(p);
            }
            var price = document.createElement('p');
            price.className = 'menu-card-price';
            price.textContent = formatMoney(Number(it.price));
            if (qty === 0) {
                var btnAdd = document.createElement('button');
                btnAdd.type = 'button';
                btnAdd.className = 'btn btn-outline btn-block';
                btnAdd.textContent = 'Add to Cart';
                btnAdd.addEventListener('click', function () {
                    setQty(id, 1);
                });
                body.appendChild(price);
                body.appendChild(btnAdd);
            } else {
                var qtyRow = document.createElement('div');
                qtyRow.className = 'qty-row';
                var minus = document.createElement('button');
                minus.type = 'button';
                minus.className = 'qty-btn';
                minus.textContent = '−';
                minus.setAttribute('aria-label', 'Decrease ' + it.name);
                var val = document.createElement('span');
                val.className = 'qty-val';
                val.textContent = String(qty);
                var plus = document.createElement('button');
                plus.type = 'button';
                plus.className = 'qty-btn';
                plus.textContent = '+';
                plus.setAttribute('aria-label', 'Increase ' + it.name);
                minus.addEventListener('click', function () {
                    setQty(id, (cart[id] || 0) - 1);
                });
                plus.addEventListener('click', function () {
                    setQty(id, (cart[id] || 0) + 1);
                });
                qtyRow.appendChild(minus);
                qtyRow.appendChild(val);
                qtyRow.appendChild(plus);
                body.appendChild(price);
                body.appendChild(qtyRow);
            }
            card.appendChild(wrap);
            card.appendChild(body);
            gridEl.appendChild(card);
        });
    }

    function escapeHtml(s) {
        return s
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatMoney(n) {
        return '₹' + n.toFixed(2);
    }

    function setQty(menuId, q) {
        if (q <= 0) delete cart[menuId];
        else cart[menuId] = q;
        renderGrid();
        renderCart();
    }

    function lineTotals() {
        var sub = 0;
        Object.keys(cart).forEach(function (k) {
            var id = Number(k);
            var qty = cart[id];
            var it = menuItems.find(function (m) {
                return Number(m.id) === id;
            });
            if (it && qty > 0) sub += Number(it.price) * qty;
        });
        sub = Math.round(sub * 100) / 100;
        var gst = Math.round(sub * (gstRate / 100) * 100) / 100;
        
        var delFee = 0;
        if (orderType && orderType.value === 'DELIVERY') {
            var service = localStorage.getItem('vp_service');
            if (service) {
                var sData = JSON.parse(service);
                delFee = Number(sData.delivery_fee || 0);
            }
        }

        var total = Math.round((sub + gst + delFee) * 100) / 100;
        return { sub: sub, gst: gst, delivery: delFee, total: total };
    }

    function renderCart() {
        if (!cartLines || !cartEmpty || !cartSummary) return;
        var ids = Object.keys(cart).filter(function (k) {
            return cart[Number(k)] > 0;
        });
        cartLines.querySelectorAll('.cart-line').forEach(function (n) {
            n.remove();
        });
        if (ids.length === 0) {
            cartEmpty.classList.remove('is-hidden');
            cartSummary.classList.add('is-hidden');
            return;
        }
        cartEmpty.classList.add('is-hidden');
        cartSummary.classList.remove('is-hidden');
        ids.forEach(function (k) {
            var id = Number(k);
            var qty = cart[id];
            var it = menuItems.find(function (m) {
                return Number(m.id) === id;
            });
            if (!it) return;
            var line = document.createElement('div');
            line.className = 'cart-line';
            var left = document.createElement('div');
            left.innerHTML =
                '<strong>' +
                escapeHtml(String(it.name)) +
                '</strong><br><span class="text-muted">' +
                qty +
                ' × ' +
                formatMoney(Number(it.price)) +
                '</span>';
            var right = document.createElement('div');
            right.style.textAlign = 'right';
            right.innerHTML =
                '<div>' +
                formatMoney(Number(it.price) * qty) +
                '</div><button type="button" class="btn btn-ghost btn-sm remove-line" data-id="' +
                id +
                '">Remove</button>';
            line.appendChild(left);
            line.appendChild(right);
            cartLines.appendChild(line);
        });
        cartLines.querySelectorAll('.remove-line').forEach(function (b) {
            b.addEventListener('click', function () {
                var id = Number(b.getAttribute('data-id'));
                delete cart[id];
                renderGrid();
                renderCart();
            });
        });
        var t = lineTotals();
        sumSub.textContent = formatMoney(t.sub);
        sumGst.textContent = formatMoney(t.gst);
        
        if (rowDeliveryFee && sumDelivery) {
            if (t.delivery > 0) {
                rowDeliveryFee.hidden = false;
                sumDelivery.textContent = formatMoney(t.delivery);
            } else {
                rowDeliveryFee.hidden = true;
            }
        }

        sumTotal.textContent = formatMoney(t.total);
    }

    function toggleOrderFields() {
        var del = orderType && orderType.value === 'DELIVERY';
        if (wrapTable) wrapTable.hidden = !!del;
        if (wrapDelivery) wrapDelivery.hidden = !del;
    }

    // Modal Control
    var checkoutModal = document.getElementById('checkoutModal');
    if (checkoutModal) {
        document.getElementById('closeCheckoutModal').onclick = closeCheckout;
        document.getElementById('btnCancelZone')?.addEventListener('click', closeCheckout);
    }

    function closeCheckout() {
        if (checkoutModal) checkoutModal.classList.remove('is-active');
    }

    function showStep(s) {
        document.querySelectorAll('.checkout-step').forEach(x => x.style.display = 'none');
        document.getElementById('step' + s).style.display = 'block';
    }

    async function loadMenu() {
        var load = document.getElementById('menuLoading');
        if (load) load.classList.add('is-visible');
        try {
            var data = await window.SRMS.apiFetch('api/menu?active=1');
            if (data && data.success && Array.isArray(data.data)) {
                menuItems = data.data.map(function (m) {
                    return Object.assign({}, m, {
                        image_url:
                            m.image_url ||
                            (window.SRMS.resolveMenuImageUrl ? window.SRMS.resolveMenuImageUrl(m) : '') ||
                            null,
                    });
                });
                renderChips();
                renderGrid();
                renderCart();
            }
        } catch (e) {
            window.SRMS.toast(e.message || 'Could not load menu', true);
        } finally {
            if (load) load.classList.remove('is-visible');
        }
    }

    if (searchEl) {
        searchEl.addEventListener('input', function () {
            renderGrid();
        });
    }
    if (orderType) {
        orderType.addEventListener('change', function() {
            toggleOrderFields();
            // Pre-fill modal field instead
            var loc = localStorage.getItem('vp_address');
            var pin = localStorage.getItem('vp_pincode');
            if (loc && pin && document.getElementById('checkoutAddr')) {
                document.getElementById('checkoutAddr').value = loc + ' (' + pin + ')';
                document.getElementById('detectedLocationInfo').textContent = '📍 Delivery to your selected location: ' + pin;
            }
            renderCart();
        });
    }
    toggleOrderFields();

    if (btnPlace) {
        btnPlace.addEventListener('click', function () {
            var ids = Object.keys(cart).filter(k => cart[k] > 0);
            if (ids.length === 0) {
                window.SRMS.toast('Cart is empty', true);
                return;
            }

            if (orderType.value === 'DINE_IN' && (!tableId || tableId.value === '0')) {
                window.SRMS.toast('Please select a table for dine-in', true);
                return;
            }

            // Adjust checkout modal for order type
            var wrapCheckoutAddr = document.getElementById('wrapCheckoutAddr');
            var stepTitle = document.getElementById('checkoutStepTitle');
            var isDineIn = orderType.value === 'DINE_IN';
            if (wrapCheckoutAddr) wrapCheckoutAddr.style.display = isDineIn ? 'none' : 'block';
            if (stepTitle) stepTitle.textContent = isDineIn ? 'Customer Details' : 'Delivery Details';

            // Pre-fill address from saved location for DELIVERY
            var loc = localStorage.getItem('vp_address');
            var pin = localStorage.getItem('vp_pincode');
            if (!isDineIn && loc && pin && document.getElementById('checkoutAddr')) {
                document.getElementById('checkoutAddr').value = loc + ' (' + pin + ')';
                document.getElementById('detectedLocationInfo').textContent = '📍 Delivery to your selected location: ' + pin;
            }

            checkoutModal.classList.add('is-active');
            showStep('Address');
        });
    }

    document.getElementById('btnNextToPayment')?.addEventListener('click', () => {
        var isDineIn = orderType && orderType.value === 'DINE_IN';
        var name = document.getElementById('checkoutName').value.trim();
        var phone = document.getElementById('checkoutPhone').value.trim();
        var addr = document.getElementById('checkoutAddr').value.trim();
        if (!name || !phone || (!isDineIn && !addr)) {
            window.SRMS.toast('Please fill all required details', true);
            return;
        }
        showStep('Payment');
    });

    document.getElementById('btnNextToConfirm')?.addEventListener('click', () => {
        var name = document.getElementById('checkoutName').value.trim();
        var phone = document.getElementById('checkoutPhone').value.trim();
        var addr = document.getElementById('checkoutAddr').value.trim();
        var method = document.querySelector('input[name="paymentMethod"]:checked').value;
        var isDineIn = orderType && orderType.value === 'DINE_IN';

        document.getElementById('confirmSummaryName').textContent = 'Name: ' + name;
        document.getElementById('confirmSummaryPhone').textContent = 'Phone: ' + phone;
        var summaryAddr = document.getElementById('confirmSummaryAddr');
        if (isDineIn) {
            summaryAddr.textContent = 'Table: ' + (tableId ? tableId.options[tableId.selectedIndex].text.trim() : 'N/A');
        } else {
            summaryAddr.textContent = 'Address: ' + addr;
        }

        if (method === 'UPI') {
            document.getElementById('upiQrSection').style.display = 'block';
            document.getElementById('upiAmount').textContent = lineTotals().total;
        } else {
            document.getElementById('upiQrSection').style.display = 'none';
        }

        showStep('Confirm');
    });

    document.getElementById('btnFinalConfirm')?.addEventListener('click', async () => {
        var btn = document.getElementById('btnFinalConfirm');
        btn.disabled = true;
        btn.textContent = 'Processing...';

        var lines = Object.keys(cart).map(k => ({ menu_item_id: Number(k), quantity: cart[k] }));
        var method = document.querySelector('input[name="paymentMethod"]:checked').value;
        var isDineIn = orderType.value === 'DINE_IN';

        var payload = {
            type: orderType.value,
            cart: lines,
            table_id: isDineIn ? Number(tableId.value) : null,
            customer_name: document.getElementById('checkoutName').value.trim(),
            customer_phone: document.getElementById('checkoutPhone').value.trim(),
            delivery_address: isDineIn ? null : document.getElementById('checkoutAddr').value.trim(),
            payment_method: method
        };

        try {
            var res = await window.SRMS.apiPostJson('api/order-place', payload);
            if (res.success) {
                document.getElementById('successOrderId').textContent = '#' + (res.order_code || '---');
                document.getElementById('successPayment').textContent = method;
                cart = {};
                renderCart();
                renderGrid();
                showStep('Success');
            } else {
                window.SRMS.toast(res.error || 'Order failed. Please try again.', true);
            }
        } catch (err) {
            window.SRMS.toast(err.message || 'Something went wrong. Please try again.', true);
        } finally {
            btn.disabled = false;
            btn.textContent = 'Confirm Order';
        }
    });

    loadMenu();
})();
