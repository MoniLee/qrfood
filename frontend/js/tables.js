(function () {
    'use strict';

    var form = document.getElementById('tableForm');
    var tid = document.getElementById('tableId');
    var label = document.getElementById('tableLabel');
    var cap = document.getElementById('tableCap');
    var status = document.getElementById('tableStatus');
    var reset = document.getElementById('tableReset');
    var body = document.getElementById('tablesBody');

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function render(rows) {
        if (!body) return;
        body.innerHTML = rows
            .map(function (t) {
                var id = Number(t.id);
                var st = String(t.status);
                var badge = st === 'occupied' ? 'badge-occ' : 'badge-avail';
                return (
                    '<tr data-table-id="' +
                    id +
                    '"><td data-label="Label"><strong>' +
                    escapeHtml(String(t.label)) +
                    '</strong></td><td data-label="Capacity">' +
                    Number(t.capacity) +
                    '</td><td data-label="Status"><span class="badge ' +
                    badge +
                    '">' +
                    escapeHtml(st) +
                    '</span></td><td data-label="Quick" class="no-print"><div class="btn-group">' +
                    '<button type="button" class="btn btn-outline btn-sm tbl-st" data-id="' +
                    id +
                    '" data-status="available">Available</button>' +
                    '<button type="button" class="btn btn-outline btn-sm tbl-st" data-id="' +
                    id +
                    '" data-status="occupied">Occupied</button></div></td>' +
                    '<td data-label="Actions" class="no-print"><div class="btn-group">' +
                    '<button type="button" class="btn btn-outline btn-sm tbl-edit" data-id="' +
                    id +
                    '">Edit</button>' +
                    '<button type="button" class="btn btn-danger btn-sm tbl-del" data-id="' +
                    id +
                    '">Delete</button></div></td></tr>'
                );
            })
            .join('');
        wire();
    }

    async function load() {
        try {
            var data = await window.SRMS.apiFetch('api/tables');
            if (data && data.success) render(data.data);
        } catch (e) {
            window.SRMS.toast(e.message || 'Could not load tables', true);
        }
    }

    function wire() {
        if (!body) return;
        body.querySelectorAll('.tbl-edit').forEach(function (b) {
            b.addEventListener('click', async function () {
                var id = Number(b.getAttribute('data-id'));
                var data = await window.SRMS.apiFetch('api/tables');
                var row = (data.data || []).find(function (r) {
                    return Number(r.id) === id;
                });
                if (!row) return;
                if (tid) tid.value = String(row.id);
                if (label) label.value = row.label;
                if (cap) cap.value = String(row.capacity);
                if (status) status.value = row.status;
            });
        });
        body.querySelectorAll('.tbl-del').forEach(function (b) {
            b.addEventListener('click', async function () {
                if (!confirm('Delete this table?')) return;
                var id = Number(b.getAttribute('data-id'));
                try {
                    await window.SRMS.apiPostJson('api/table-delete', { id: id });
                    window.SRMS.toast('Table removed');
                    await load();
                } catch (e) {
                    window.SRMS.toast(e.message || 'Delete failed', true);
                }
            });
        });
        body.querySelectorAll('.tbl-st').forEach(function (b) {
            b.addEventListener('click', async function () {
                var id = Number(b.getAttribute('data-id'));
                var st = b.getAttribute('data-status');
                try {
                    await window.SRMS.apiPostJson('api/table-status', { id: id, status: st });
                    await load();
                } catch (e) {
                    window.SRMS.toast(e.message || 'Update failed', true);
                }
            });
        });
    }

    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            var payload = {
                label: label ? label.value.trim() : '',
                capacity: cap ? Number(cap.value) : 4,
                status: status ? status.value : 'available',
            };
            var id = tid && tid.value ? Number(tid.value) : 0;
            if (id > 0) payload.id = id;
            try {
                await window.SRMS.apiPostJson('api/table-save', payload);
                window.SRMS.toast('Table saved');
                if (reset) reset.click();
                await load();
            } catch (err) {
                window.SRMS.toast(err.message || 'Save failed', true);
            }
        });
    }

    if (reset) {
        reset.addEventListener('click', function () {
            if (tid) tid.value = '';
            if (label) label.value = '';
            if (cap) cap.value = '4';
            if (status) status.value = 'available';
        });
    }

    wire();
})();
