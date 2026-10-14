(function () {
    'use strict';

    var el = document.getElementById('srms-dashboard-json');
    /** @type {{ revenueByDay:any[], statusCounts:any[], popular:any[] }} */
    var boot = el ? JSON.parse(el.textContent || '{}') : {};

    /** @param {string} id @param {object} cfg Chart.js configuration */
    function makeChart(id, cfg) {
        var canvas = document.getElementById(id);
        if (!canvas || typeof Chart === 'undefined') return null;
        var ctx = canvas.getContext('2d');
        if (!ctx) return null;
        return new Chart(ctx, cfg);
    }

    var revChart = makeChart('revenueChart', {
        type: 'line',
        data: {
            labels: (boot.revenueByDay || []).map(function (r) {
                return String(r.day || '');
            }),
            datasets: [
                {
                    label: 'Revenue (₹)',
                    data: (boot.revenueByDay || []).map(function (r) {
                        return Number(r.total);
                    }),
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37,99,235,0.15)',
                    fill: true,
                    tension: 0.3,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true },
            },
        },
    });

    var statusLabels = (boot.statusCounts || []).map(function (s) {
        return String(s.status);
    });
    var statusData = (boot.statusCounts || []).map(function (s) {
        return Number(s.cnt);
    });
    var statusChart = makeChart('statusChart', {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [
                {
                    data: statusData,
                    backgroundColor: ['#facc15', '#3b82f6', '#34d399', '#94a3b8'],
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
        },
    });

    function money(n) {
        return '₹' + Number(n).toFixed(2);
    }

    async function refresh() {
        try {
            var data = await window.SRMS.apiFetch('api/stats');
            if (!data || !data.success || !data.data) return;
            var d = data.data;
            var t = document.getElementById('statTotalOrders');
            if (t) t.textContent = String(d.total_orders);
            var r = document.getElementById('statRevenue');
            if (r) r.textContent = money(d.revenue);
            var ot = document.getElementById('statOrdersToday');
            if (ot) ot.textContent = String(d.orders_today);
            var rt = document.getElementById('statRevenueToday');
            if (rt) rt.textContent = money(d.revenue_today);
            var oc = document.getElementById('statOccupied');
            if (oc) oc.textContent = String(d.tables_occupied);
            var av = document.getElementById('statAvailable');
            if (av) av.textContent = String(d.tables_available);

            var pop = document.getElementById('popularList');
            if (pop && Array.isArray(d.popular)) {
                if (d.popular.length === 0) {
                    pop.innerHTML = '<p class="text-muted mt-0">No completed orders yet.</p>';
                } else {
                    pop.innerHTML =
                        '<ul class="kitchen-items">' +
                        d.popular
                            .map(function (p) {
                                return (
                                    '<li><strong>' +
                                    String(p.name).replace(/</g, '&lt;') +
                                    '</strong> — ' +
                                    Number(p.total_qty) +
                                    ' sold</li>'
                                );
                            })
                            .join('') +
                        '</ul>';
                }
            }

            if (revChart && d.revenue_by_day) {
                revChart.data.labels = d.revenue_by_day.map(function (x) {
                    return String(x.day);
                });
                revChart.data.datasets[0].data = d.revenue_by_day.map(function (x) {
                    return Number(x.total);
                });
                revChart.update();
            }
            if (statusChart && d.status_counts) {
                statusChart.data.labels = d.status_counts.map(function (x) {
                    return String(x.status);
                });
                statusChart.data.datasets[0].data = d.status_counts.map(function (x) {
                    return Number(x.cnt);
                });
                statusChart.update();
            }
        } catch (e) {
            window.SRMS.toast(e.message || 'Dashboard refresh failed', true);
        }
    }

    async function refreshZones() {
        var body = document.getElementById('zoneTableBody');
        if (!body) return;
        try {
            var data = await window.SRMS.apiFetch('api/zone-list');
            if (data && data.success && Array.isArray(data.data)) {
                if (data.data.length === 0) {
                    body.innerHTML = '<tr><td colspan="5" class="text-muted">No zones defined.</td></tr>';
                } else {
                    body.innerHTML = data.data.map(function(z) {
                        return '<tr>' +
                            '<td><strong>' + String(z.name).replace(/</g, '&lt;') + '</strong></td>' +
                            '<td>' + String(z.pincodes).replace(/</g, '&lt;') + '</td>' +
                            '<td>' + money(Number(z.delivery_fee)) + '</td>' +
                            '<td>' + money(Number(z.min_order)) + '</td>' +
                            '<td>' + 
                                '<button class="btn btn-ghost btn-sm" onclick="alert(\'Edit Zone ID: ' + z.id + '\')">Edit</button>' +
                            '</td>' +
                            '</tr>';
                    }).join('');
                }
            }
        } catch (e) {
            console.error('Zone refresh failed', e);
        }
    }

    // Zone Modal logic
    var zModal = document.getElementById('zoneModal');
    var zForm = document.getElementById('zoneForm');
    var btnAddZ = document.getElementById('btnAddZone');
    
    function openZone(z = null) {
        if (!zModal || !zForm) return;
        document.getElementById('zoneModalTitle').textContent = z ? 'Edit Delivery Zone' : 'Add Delivery Zone';
        document.getElementById('zoneId').value = z ? z.id : '';
        document.getElementById('zoneName').value = z ? z.name : '';
        document.getElementById('zonePincodes').value = z ? z.pincodes : '';
        document.getElementById('zoneFee').value = z ? z.delivery_fee : '0.00';
        document.getElementById('zoneMin').value = z ? z.min_order : '0.00';
        document.getElementById('zoneTime').value = z ? z.estimated_time : '30-45 mins';
        document.getElementById('zoneActive').checked = z ? !!Number(z.is_active) : true;
        
        zModal.classList.add('is-active');
        document.body.style.overflow = 'hidden';
    }

    function closeZone() {
        if (zModal) {
            zModal.classList.remove('is-active');
            document.body.style.overflow = '';
        }
    }

    if (btnAddZ) btnAddZ.addEventListener('click', function() { openZone(); });
    if (document.getElementById('closeZoneModal')) document.getElementById('closeZoneModal').addEventListener('click', closeZone);
    if (document.getElementById('btnCancelZone')) document.getElementById('btnCancelZone').addEventListener('click', closeZone);

    if (zForm) {
        zForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            var payload = {
                id: document.getElementById('zoneId').value,
                name: document.getElementById('zoneName').value.trim(),
                pincodes: document.getElementById('zonePincodes').value.trim(),
                delivery_fee: parseFloat(document.getElementById('zoneFee').value),
                min_order: parseFloat(document.getElementById('zoneMin').value),
                estimated_time: document.getElementById('zoneTime').value.trim(),
                is_active: document.getElementById('zoneActive').checked
            };
            try {
                var res = await window.SRMS.apiPostJson('api/zone-save', payload);
                if (res.success) {
                    window.SRMS.toast('Zone saved successfully');
                    closeZone();
                    refreshZones();
                }
            } catch (err) {
                window.SRMS.toast(err.message || 'Failed to save zone', true);
            }
        });
    }

    // Delegate edit buttons (since they're dynamic)
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('edit-zone-btn')) {
            var zid = e.target.dataset.id;
            // Fetch fresh or use cached? Let's use current data list
            window.SRMS.apiFetch('api/zone-list').then(function(res) {
                var z = res.data.find(function(x) { return String(x.id) === String(zid); });
                if (z) openZone(z);
            });
        }
    });

    async function refreshZones() {
        var body = document.getElementById('zoneTableBody');
        if (!body) return;
        try {
            var data = await window.SRMS.apiFetch('api/zone-list');
            if (data && data.success && Array.isArray(data.data)) {
                if (data.data.length === 0) {
                    body.innerHTML = '<tr><td colspan="5" class="text-muted">No zones defined.</td></tr>';
                } else {
                    body.innerHTML = data.data.map(function(z) {
                        return '<tr>' +
                            '<td><strong>' + String(z.name).replace(/</g, '&lt;') + '</strong></td>' +
                            '<td><span style="font-size:0.85rem; color:var(--text-muted)">' + String(z.pincodes).replace(/</g, '&lt;') + '</span></td>' +
                            '<td>' + money(Number(z.delivery_fee)) + '</td>' +
                            '<td>' + money(Number(z.min_order)) + '</td>' +
                            '<td>' + 
                                '<button class="btn btn-ghost btn-sm edit-zone-btn" data-id="' + z.id + '">Edit</button>' +
                            '</td>' +
                            '</tr>';
                    }).join('');
                }
            }
        } catch (e) {
            console.error('Zone refresh failed', e);
        }
    }

    var btn = document.getElementById('refreshDashboard');
    if (btn) btn.addEventListener('click', function() {
        refresh();
        refreshZones();
    });
    
    refreshZones();
    window.setInterval(refresh, window.SRMS.pollMs || 8000);
})();
