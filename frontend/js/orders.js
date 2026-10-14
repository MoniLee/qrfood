(function () {
    'use strict';

    var main = document.getElementById('main-content');
    var isCustomer = main && main.getAttribute('data-customer') === '1';
    var body = document.getElementById('ordersBody');
    var note = document.getElementById('ordersSyncNote');
    var btn = document.getElementById('btnRefreshOrders');

    function completedLabel(type) {
        return type === 'DELIVERY' ? 'delivered' : 'served';
    }

    function badgeClass(status) {
        return 'badge badge-' + String(status).replace(/[^a-z]/g, '');
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderRow(o, includeStatusSelect) {
        var id = Number(o.id);
        var type = String(o.type || '');
        var detail =
            type === 'DINE_IN'
                ? escapeHtml(String(o.table_label || '—'))
                : escapeHtml(String(o.customer_name || '')) +
                  '<br><span class="text-muted">' +
                  escapeHtml(String(o.customer_phone || '')) +
                  '</span>';
        var opts = ['pending', 'preparing', 'ready', 'completed']
            .map(function (st) {
                var label = st === 'completed' ? completedLabel(type) : st;
                var sel = String(o.status) === st ? ' selected' : '';
                return '<option value="' + st + '"' + sel + '>' + label + '</option>';
            })
            .join('');
        var statusControl = includeStatusSelect
            ? '<select class="form-select status-select btn-sm" style="min-width:120px;padding:0.35rem;" data-order-id="' +
              id +
              '" aria-label="Update status">' +
              opts +
              '</select>'
            : '';
        return (
            '<tr data-order-id="' +
            id +
            '">' +
            '<td data-label="Code"><strong>' +
            escapeHtml(String(o.order_code)) +
            '</strong></td>' +
            '<td data-label="Type">' +
            escapeHtml(type) +
            '</td>' +
            '<td data-label="Details">' +
            detail +
            '</td>' +
            '<td data-label="Status"><span class="' +
            badgeClass(o.status) +
            '">' +
            escapeHtml(String(o.status)) +
            '</span></td>' +
            '<td data-label="Total">₹' +
            Number(o.total).toFixed(2) +
            '</td>' +
            '<td data-label="Time" class="text-muted">' +
            escapeHtml(String(o.created_at)) +
            '</td>' +
            '<td data-label="Actions" class="no-print">' +
            '<div class="btn-group">' +
            '<a class="btn btn-outline btn-sm" href="' +
            window.SRMS.baseUrl +
            '/invoice/' +
            id +
            '">Invoice</a>' +
            statusControl +
            '</div></td></tr>'
        );
    }

    function wireSelects() {
        if (!body) return;
        body.querySelectorAll('.status-select').forEach(function (sel) {
            sel.addEventListener('change', async function () {
                var orderId = Number(sel.getAttribute('data-order-id'));
                var status = sel.value;
                sel.disabled = true;
                try {
                    await window.SRMS.apiPostJson('api/order-status', {
                        order_id: orderId,
                        status: status,
                    });
                    window.SRMS.toast('Status updated');
                } catch (e) {
                    window.SRMS.toast(e.message || 'Update failed', true);
                } finally {
                    sel.disabled = false;
                }
            });
        });
    }

    async function load() {
        if (note) note.textContent = 'Syncing…';
        try {
            var data = await window.SRMS.apiFetch('api/order-list?limit=80');
            if (data && data.success && Array.isArray(data.data) && body) {
                body.innerHTML = data.data.map(function (o) {
                    return renderRow(o, true);
                }).join('');
                wireSelects();
            }
            if (note) {
                note.textContent = 'Last updated: ' + new Date().toLocaleTimeString();
            }
        } catch (e) {
            if (note) note.textContent = '';
            window.SRMS.toast(e.message || 'Could not load orders', true);
        }
    }

    wireSelects();
    if (btn) {
        btn.addEventListener('click', function () {
            if (isCustomer) {
                window.location.reload();
            } else {
                load();
            }
        });
    }
    if (!isCustomer) {
        window.setInterval(load, window.SRMS.pollMs || 5000);
    } else if (note) {
        note.textContent = '';
    }
})();
