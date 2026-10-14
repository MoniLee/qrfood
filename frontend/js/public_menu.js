(function () {
    'use strict';

    var el = document.getElementById('srms-pos-initial');
    var initial = el ? JSON.parse(el.textContent || '{}') : { menu: [], gstRate: 18 };
    var menuItems = initial.menu || [];
    var gstRate = typeof initial.gstRate === 'number' ? initial.gstRate : 18;

    var searchEl = document.getElementById('menuSearch');
    var chipsEl = document.getElementById('categoryChips');
    var gridEl = document.getElementById('menuGrid');

    var cartLines = document.getElementById('cartLines');
    var cartEmpty = document.getElementById('cartEmpty');
    var cartSummary = document.getElementById('cartSummary');
    var sumSub = document.getElementById('sumSub');
    var sumGst = document.getElementById('sumGst');
    var sumTotal = document.getElementById('sumTotal');
    var sumDelivery = document.getElementById('sumDelivery');
    var rowDeliveryFee = document.getElementById('rowDeliveryFee');
    var orderType = document.getElementById('orderType');
    var wrapTable = document.getElementById('wrapTable');
    var btnPlace = document.getElementById('btnPlace');
    var selectedTableInput = document.getElementById('selectedTable');
    var tableError = document.getElementById('tableError');

    // Modal Controls
    var checkoutModal = document.getElementById('checkoutModal');
    if (checkoutModal) {
        document.getElementById('closeCheckoutModal').onclick = () => checkoutModal.classList.remove('is-active');
    }

    function showStep(s) {
        document.querySelectorAll('.checkout-step').forEach(x => x.style.display = 'none');
        document.getElementById('step' + s).style.display = 'block';
    }

    if (selectedTableInput) {
        selectedTableInput.addEventListener('change', function () {
            if (tableError) tableError.classList.remove('is-visible');
        });
    }

    var cart = {};
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
            gridEl.innerHTML = '<p class="text-muted">No dishes match your search.</p>';
            return;
        }
        list.forEach(function (it) {
            var id = Number(it.id);
            var qty = cart[id] || 0;
            var card = document.createElement('article');
            card.className = 'menu-card';
            card.dataset.id = id;
            var imgSrc = window.SRMS.resolveMenuImageUrl ? window.SRMS.resolveMenuImageUrl(it) : '';
            var img;
            var hasImage = !!imgSrc;
            if (hasImage) {
                img = document.createElement('img');
                img.alt = String(it.name || 'Dish photo');
                img.className = 'menu-card-img';
                img.loading = 'lazy';
                img.addEventListener('load', function () {
                    img.classList.add('is-loaded');
                    if (img.parentElement) img.parentElement.classList.remove('is-loading');
                });
                img.addEventListener('error', function () {
                    img.classList.add('is-error');
                    if (img.parentElement) {
                        img.parentElement.classList.remove('is-loading');
                        img.parentElement.classList.add('is-error');
                    }
                });
                img.src = imgSrc;
            } else {
                img = document.createElement('div');
                img.className = 'menu-card-placeholder';
                img.textContent = 'Velvet Plate';
            }
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
                p.style.flex = '1';
                p.textContent = String(it.description);
                body.appendChild(p);
            }

            var price = document.createElement('p');
            price.className = 'menu-card-price';
            // We removed inline styles so main.css controls the size responsively
            price.style.margin = '0';
            price.textContent = formatMoney(Number(it.price));

            var actionRow = document.createElement('div');
            actionRow.className = 'action-row';
            renderActionRowInner(actionRow, id, qty);

            var bottomRow = document.createElement('div');
            bottomRow.style.display = 'flex';
            bottomRow.style.flexWrap = 'wrap';
            bottomRow.style.gap = '0.5rem';
            bottomRow.style.justifyContent = 'space-between';
            bottomRow.style.alignItems = 'center';
            bottomRow.style.marginTop = 'auto'; // Push to bottom of card
            bottomRow.style.paddingTop = '0.5rem';

            bottomRow.appendChild(price);
            bottomRow.appendChild(actionRow);

            body.appendChild(bottomRow);

            var wrapper = document.createElement('div');
            wrapper.className = 'menu-card-img-wrapper' + (hasImage ? ' is-loading' : '');
            
            if (id % 3 === 0) {
                var ribbon = document.createElement('span');
                ribbon.className = 'menu-card-ribbon';
                ribbon.setAttribute('aria-hidden', 'true');
                ribbon.textContent = 'Chef’s pick';
                wrapper.appendChild(ribbon);
            }
            
            wrapper.appendChild(img);

            card.appendChild(wrapper);
            card.appendChild(body);
            gridEl.appendChild(card);
        });
    }

    function escapeHtml(s) {
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function formatMoney(n) {
        return '₹' + n.toFixed(2);
    }

    function renderActionRowInner(actionRow, id, qty) {
        actionRow.innerHTML = '';
        if (qty > 0) {
            var qtyRow = document.createElement('div');
            qtyRow.className = 'qty-row';
            var minus = document.createElement('button');
            minus.type = 'button';
            minus.className = 'qty-btn';
            minus.textContent = '−';
            var val = document.createElement('span');
            val.className = 'qty-val';
            val.textContent = String(qty);
            var plus = document.createElement('button');
            plus.type = 'button';
            plus.className = 'qty-btn';
            plus.textContent = '+';
            minus.addEventListener('click', function () {
                setQty(id, (cart[id] || 0) - 1);
            });
            plus.addEventListener('click', function () {
                setQty(id, (cart[id] || 0) + 1);
            });
            qtyRow.appendChild(minus);
            qtyRow.appendChild(val);
            qtyRow.appendChild(plus);
            actionRow.appendChild(qtyRow);
        } else {
            var btnAdd = document.createElement('button');
            btnAdd.type = 'button';
            btnAdd.className = 'btn btn-outline btn-sm';
            btnAdd.style.padding = '0.35rem 0.75rem';
            btnAdd.textContent = 'Add';
            btnAdd.addEventListener('click', function () {
                setQty(id, 1);
                if (window.SRMS.toast) window.SRMS.toast('Added to your tray — adjust quantity here anytime.');
            });
            actionRow.appendChild(btnAdd);
        }
    }

    function updateCardQty(id, qty) {
        var card = document.querySelector('.menu-card[data-id="' + id + '"]');
        if (!card) return;
        var actionRow = card.querySelector('.action-row');
        if (!actionRow) return;
        renderActionRowInner(actionRow, id, qty);
    }

    function setQty(menuId, q) {
        if (q <= 0) delete cart[menuId];
        else cart[menuId] = q;
        updateCardQty(menuId, q <= 0 ? 0 : q);
        renderCart();
    }

    function lineTotals() {
        var sub = 0;
        Object.keys(cart).forEach(function (k) {
            var id = Number(k);
            var qty = cart[id];
            var it = menuItems.find(function (m) { return Number(m.id) === id; });
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
        var ids = Object.keys(cart).filter(function (k) { return cart[Number(k)] > 0; });
        cartLines.querySelectorAll('.cart-line').forEach(function (n) { n.remove(); });
        if (ids.length === 0) {
            cartEmpty.classList.remove('is-hidden');
            cartSummary.classList.remove('is-hidden'); // The wrapper stays, but logic needs careful check
            // Actually public_menu has a slightly different wrap. Let's fix it properly.
            cartSummary.classList.add('is-hidden');
            return;
        }
        cartEmpty.classList.add('is-hidden');
        cartSummary.classList.remove('is-hidden');
        ids.forEach(function (k) {
            var id = Number(k);
            var qty = cart[id];
            var it = menuItems.find(function (m) { return Number(m.id) === id; });
            if (!it) return;
            var line = document.createElement('div');
            line.className = 'cart-line';
            var left = document.createElement('div');
            left.innerHTML =
                '<strong>' + escapeHtml(String(it.name)) + '</strong><br><span class="text-muted">' +
                qty + ' × ' + formatMoney(Number(it.price)) + '</span>';
            var right = document.createElement('div');
            right.style.textAlign = 'right';
            right.innerHTML =
                '<div>' + formatMoney(Number(it.price) * qty) + '</div><button type="button" class="btn btn-ghost btn-sm remove-line" data-id="' + id + '">Remove</button>';
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

    if (searchEl) searchEl.addEventListener('input', renderGrid);

    if (orderType) {
        orderType.addEventListener('change', function () {
            var isDineIn = orderType.value === 'DINE_IN';
            if (wrapTable) wrapTable.style.display = isDineIn ? 'block' : 'none';
            renderCart();
        });
    }

    if (btnPlace) {
        btnPlace.addEventListener('click', function () {
            var ids = Object.keys(cart).filter(k => cart[k] > 0);
            if (ids.length === 0) {
                window.SRMS.toast('Cart is empty', true);
                return;
            }

            if (orderType.value === 'DINE_IN') {
                var selectedId = selectedTableInput ? selectedTableInput.value : '0';

                if (!selectedId || selectedId === '0') {
                    window.SRMS.toast('Please select a table first', true);
                    return;
                }
            }

            // Show/hide address field based on order type
            var wrapAddr = document.getElementById('wrapCheckoutAddr');
            var isDineIn = orderType.value === 'DINE_IN';
            if (wrapAddr) wrapAddr.style.display = isDineIn ? 'none' : 'block';

            if (!isDineIn) {
                var savedLoc = localStorage.getItem('vp_address');
                var savedPin = localStorage.getItem('vp_pincode');
                var locInfo = document.getElementById('detectedLocationInfo');
                if (savedLoc && savedPin) {
                    var addrEl = document.getElementById('checkoutAddr');
                    if (addrEl) addrEl.value = savedLoc + ' (' + savedPin + ')';
                    if (locInfo) locInfo.textContent = '\uD83D\uDCCD Delivering to: ' + savedPin;
                }
            }

            // Update modal title
            var modalTitle = document.getElementById('checkoutModalTitle');
            if (modalTitle) modalTitle.textContent = isDineIn ? 'Dine-in Details' : 'Delivery Details';

            checkoutModal.classList.add('is-active');
            showStep('Address');
        });
    }

    document.getElementById('btnNextToPayment')?.addEventListener('click', () => {
        var name = (document.getElementById('checkoutName').value || '').trim();
        var phone = (document.getElementById('checkoutPhone').value || '').trim();
        var addr = (document.getElementById('checkoutAddr')?.value || '').trim();

        if (!name || !phone || (orderType.value === 'DELIVERY' && !addr)) {
            window.SRMS.toast('Please fill in all required details', true);
            return;
        }
        showStep('Payment');
    });

    document.getElementById('btnNextToConfirm')?.addEventListener('click', () => {
        var name = (document.getElementById('checkoutName')?.value || '').trim();
        var phone = (document.getElementById('checkoutPhone')?.value || '').trim();
        var addr = (document.getElementById('checkoutAddr')?.value || '').trim();
        var method = document.querySelector('input[name="paymentMethod"]:checked').value;

        document.getElementById('confirmSummaryName').textContent = 'Name: ' + name;
        document.getElementById('confirmSummaryPhone').textContent = 'Phone: ' + phone;

        var summaryAddr = document.getElementById('confirmSummaryAddr');
        if (summaryAddr) {
            if (orderType.value === 'DELIVERY') {
                summaryAddr.textContent = 'Address: ' + addr;
                summaryAddr.style.display = 'block';
            } else {
                summaryAddr.style.display = 'none';
            }
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

        var tableIdValue = isDineIn && selectedTableInput ? Number(selectedTableInput.value || 0) : 0;

        var payload = {
            type: orderType.value,
            cart: lines,
            table_id: isDineIn ? tableIdValue : null,
            customer_name: (document.getElementById('checkoutName').value || '').trim(),
            customer_phone: (document.getElementById('checkoutPhone').value || '').trim(),
            delivery_address: isDineIn ? null : (document.getElementById('checkoutAddr')?.value || '').trim(),
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
            btn.textContent = 'Confirm & Place Order';
        }
    });

    var gstPctLabel = document.getElementById('gstPctLabel');
    if (gstPctLabel) gstPctLabel.textContent = String(gstRate);

    renderChips();
    renderGrid();
    renderCart();

})();
