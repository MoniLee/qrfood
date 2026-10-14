(function () {
    'use strict';

    var grid = document.getElementById('kitchenGrid');
    var empty = document.getElementById('kitchenEmpty');
    var loadEl = document.getElementById('kitchenLoading');
    var sync = document.getElementById('kitchenSync');
    var clock = document.getElementById('kitchenClock');

    function tick() {
        if (clock) clock.textContent = new Date().toLocaleString();
    }
    tick();
    window.setInterval(tick, 1000);

    function nextStatus(cur) {
        var order = ['pending', 'preparing', 'ready', 'completed'];
        var i = order.indexOf(cur);
        if (i < 0 || i >= order.length - 1) return cur;
        return order[i + 1];
    }

    function labelForNext(type, st) {
        var n = nextStatus(st);
        if (n === 'completed') return type === 'DELIVERY' ? 'Mark delivered' : 'Mark served';
        if (n === 'preparing') return 'Start preparing';
        if (n === 'ready') return 'Mark ready';
        return 'Update';
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderCard(entry) {
        var o = entry.order;
        var items = entry.items || [];
        var id = Number(o.id);
        var type = String(o.type || '');
        var st = String(o.status || '');
        var head =
            type === 'DINE_IN'
                ? 'Table ' + escapeHtml(String(o.table_label || '—'))
                : 'Delivery · ' + escapeHtml(String(o.customer_name || ''));
        var meta =
            escapeHtml(String(o.order_code)) +
            ' · ' +
            escapeHtml(type) +
            ' · ' +
            escapeHtml(st);
        var lis = items
            .map(function (li) {
                return (
                    '<li><strong>' +
                    escapeHtml(String(li.item_name || '')) +
                    '</strong> × ' +
                    Number(li.quantity) +
                    '</li>'
                );
            })
            .join('');
        var ns = nextStatus(st);
        var primary =
            ns !== st
                ? '<button type="button" class="btn btn-primary btn-block kitchen-advance" data-id="' +
                  id +
                  '" data-next="' +
                  ns +
                  '">' +
                  labelForNext(type, st) +
                  '</button>'
                : '';
        return (
            '<article class="kitchen-card" data-order-id="' +
            id +
            '"><h3>' +
            head +
            '</h3><p class="kitchen-meta">' +
            meta +
            '</p><ul class="kitchen-items">' +
            lis +
            '</ul><div class="kitchen-actions btn-group">' +
            primary +
            '</div></article>'
        );
    }

    async function load() {
        if (sync) sync.textContent = 'Refreshing…';
        if (loadEl) loadEl.classList.add('is-visible');
        try {
            var data = await window.SRMS.apiFetch('api/kitchen-orders');
            if (data && data.success && Array.isArray(data.data)) {
                var list = data.data;
                if (!grid || !empty) return;
                if (list.length === 0) {
                    grid.hidden = true;
                    empty.hidden = false;
                    grid.innerHTML = '';
                } else {
                    empty.hidden = true;
                    grid.hidden = false;
                    grid.innerHTML = list.map(renderCard).join('');
                    grid.querySelectorAll('.kitchen-advance').forEach(function (btn) {
                        btn.addEventListener('click', async function () {
                            var oid = Number(btn.getAttribute('data-id'));
                            var nx = btn.getAttribute('data-next');
                            btn.disabled = true;
                            try {
                                await window.SRMS.apiPostJson('api/kitchen-status', {
                                    order_id: oid,
                                    status: nx,
                                });
                                window.SRMS.toast('Updated');
                                await load();
                            } catch (e) {
                                window.SRMS.toast(e.message || 'Failed', true);
                                btn.disabled = false;
                            }
                        });
                    });
                }
                if (sync) sync.textContent = 'Last sync: ' + new Date().toLocaleTimeString();
            }
        } catch (e) {
            if (sync) sync.textContent = '';
            window.SRMS.toast(e.message || 'Kitchen sync failed', true);
            if (grid) {
                grid.hidden = true;
                grid.innerHTML = '';
            }
            if (empty) {
                empty.hidden = false;
                empty.textContent = 'Could not load kitchen queue. Check connection and try again.';
            }
        } finally {
            if (loadEl) loadEl.classList.remove('is-visible');
        }
    }

    load();
    window.setInterval(load, window.SRMS.pollMs || 5000);
})();
