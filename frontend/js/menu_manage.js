(function () {
    'use strict';

    var el = document.getElementById('srms-menu-initial');
    /** @type {{ items: any[], categories: any[] }} */
    var initial = el ? JSON.parse(el.textContent || '{}') : { items: [], categories: [] };
    var categories = initial.categories || [];
    var items = initial.items || [];

    var catBody = document.getElementById('catBody');
    var catForm = document.getElementById('catForm');
    var catId = document.getElementById('catId');
    var catName = document.getElementById('catName');
    var catSort = document.getElementById('catSort');
    var catReset = document.getElementById('catReset');

    var itemBody = document.getElementById('itemBody');
    var itemForm = document.getElementById('itemForm');
    var itemId = document.getElementById('itemId');
    var itemName = document.getElementById('itemName');
    var itemDesc = document.getElementById('itemDesc');
    var itemPrice = document.getElementById('itemPrice');
    var itemCategory = document.getElementById('itemCategory');
    var itemImage = document.getElementById('itemImage');
    var itemImageUrl = document.getElementById('itemImageUrl');
    var itemActive = document.getElementById('itemActive');
    var itemReset = document.getElementById('itemReset');
    var itemPreview = document.getElementById('itemPreview');

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function fillCategorySelect() {
        if (!itemCategory) return;
        itemCategory.innerHTML = '';
        categories.forEach(function (c) {
            var o = document.createElement('option');
            o.value = String(c.id);
            o.textContent = c.name;
            itemCategory.appendChild(o);
        });
    }

    function renderCategories() {
        if (!catBody) return;
        categories.sort(function (a, b) {
            return (a.sort_order || 0) - (b.sort_order || 0) || String(a.name).localeCompare(String(b.name));
        });
        catBody.innerHTML = categories
            .map(function (c) {
                return (
                    '<tr data-cat="' +
                    c.id +
                    '"><td data-label="Name">' +
                    escapeHtml(String(c.name)) +
                    '</td><td data-label="Sort">' +
                    Number(c.sort_order) +
                    '</td><td data-label="Actions" class="no-print"><div class="btn-group">' +
                    '<button type="button" class="btn btn-outline btn-sm cat-edit" data-id="' +
                    c.id +
                    '">Edit</button>' +
                    '<button type="button" class="btn btn-danger btn-sm cat-del" data-id="' +
                    c.id +
                    '">Delete</button></div></td></tr>'
                );
            })
            .join('');
        catBody.querySelectorAll('.cat-edit').forEach(function (b) {
            b.addEventListener('click', function () {
                var id = Number(b.getAttribute('data-id'));
                var c = categories.find(function (x) {
                    return Number(x.id) === id;
                });
                if (!c || !catId || !catName || !catSort) return;
                catId.value = String(c.id);
                catName.value = c.name;
                catSort.value = String(c.sort_order || 0);
            });
        });
        catBody.querySelectorAll('.cat-del').forEach(function (b) {
            b.addEventListener('click', async function () {
                if (!confirm('Delete this category?')) return;
                var id = Number(b.getAttribute('data-id'));
                try {
                    await window.SRMS.apiPostJson('api/category-delete', { id: id });
                    categories = categories.filter(function (x) {
                        return Number(x.id) !== id;
                    });
                    fillCategorySelect();
                    renderCategories();
                    renderItems();
                    window.SRMS.toast('Category deleted');
                } catch (e) {
                    window.SRMS.toast(e.message || 'Cannot delete', true);
                }
            });
        });
    }

    function renderItems() {
        if (!itemBody) return;
        itemBody.innerHTML = items
            .map(function (m) {
                return (
                    '<tr data-item="' +
                    m.id +
                    '"><td data-label="Item"><strong>' +
                    escapeHtml(String(m.name)) +
                    '</strong><br><span class="text-muted">' +
                    escapeHtml(String(m.description || '').slice(0, 80)) +
                    '</span></td><td data-label="Category">' +
                    escapeHtml(String(m.category_name || '')) +
                    '</td><td data-label="Price">₹' +
                    Number(m.price).toFixed(2) +
                    '</td><td data-label="Active">' +
                    (m.is_active ? 'Yes' : 'No') +
                    '</td><td data-label="Actions" class="no-print"><div class="btn-group">' +
                    '<button type="button" class="btn btn-outline btn-sm item-edit" data-id="' +
                    m.id +
                    '">Edit</button>' +
                    '<button type="button" class="btn btn-danger btn-sm item-del" data-id="' +
                    m.id +
                    '">Delete</button></div></td></tr>'
                );
            })
            .join('');
        itemBody.querySelectorAll('.item-edit').forEach(function (b) {
            b.addEventListener('click', function () {
                var id = Number(b.getAttribute('data-id'));
                var m = items.find(function (x) {
                    return Number(x.id) === id;
                });
                if (!m) return;
                itemId.value = String(m.id);
                itemName.value = m.name;
                itemDesc.value = m.description || '';
                itemPrice.value = String(m.price);
                itemCategory.value = String(m.category_id);
                itemActive.checked = !!m.is_active;
                if (itemImage) itemImage.value = '';
                if (itemImageUrl) {
                    var p = String(m.image_path || '');
                    itemImageUrl.value = /^https?:\/\//i.test(p) ? p : '';
                }
                if (itemPreview) {
                    var u = m.image_url || (window.SRMS.resolveMenuImageUrl ? window.SRMS.resolveMenuImageUrl(m) : '');
                    itemPreview.innerHTML = u
                        ? '<img src="' +
                          escapeHtml(u) +
                          '" alt="" class="item-preview-img">'
                        : '';
                }
            });
        });
        itemBody.querySelectorAll('.item-del').forEach(function (b) {
            b.addEventListener('click', async function () {
                if (!confirm('Delete this menu item?')) return;
                var id = Number(b.getAttribute('data-id'));
                try {
                    await window.SRMS.apiPostJson('api/menu-delete', { id: id });
                    items = items.filter(function (x) {
                        return Number(x.id) !== id;
                    });
                    renderItems();
                    window.SRMS.toast('Item deleted');
                } catch (e) {
                    window.SRMS.toast(e.message || 'Delete failed', true);
                }
            });
        });
    }

    async function uploadImage() {
        if (!itemImage || !itemImage.files || !itemImage.files[0]) return null;
        var fd = new FormData();
        fd.append('image', itemImage.files[0]);
        var base = (window.SRMS && window.SRMS.baseUrl) || '';
        var url = base + '/api/menu-upload';
        var res = await fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            body: fd,
        });
        var text = await res.text();
        var data = JSON.parse(text || '{}');
        if (!res.ok || !data.success) {
            throw new Error(data.error || 'Upload failed');
        }
        return data.path;
    }

    if (catForm) {
        catForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!catName || !catSort) return;
            var payload = {
                name: catName.value.trim(),
                sort_order: Number(catSort.value) || 0,
            };
            var cid = catId && catId.value ? Number(catId.value) : 0;
            if (cid > 0) payload.id = cid;
            try {
                var res = await window.SRMS.apiPostJson('api/category-save', payload);
                if (res.id) {
                    await reloadAll();
                    window.SRMS.toast('Category saved');
                    if (catReset) catReset.click();
                }
            } catch (err) {
                window.SRMS.toast(err.message || 'Save failed', true);
            }
        });
    }

    if (catReset) {
        catReset.addEventListener('click', function () {
            if (catId) catId.value = '';
            if (catName) catName.value = '';
            if (catSort) catSort.value = '0';
        });
    }

    if (itemForm) {
        itemForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            try {
                var pathExtra = await uploadImage();
                var payload = {
                    name: itemName ? itemName.value.trim() : '',
                    description: itemDesc ? itemDesc.value.trim() : '',
                    price: itemPrice ? Number(itemPrice.value) : 0,
                    category_id: itemCategory ? Number(itemCategory.value) : 0,
                    is_active: itemActive && itemActive.checked ? 1 : 0,
                };
                var iid = itemId && itemId.value ? Number(itemId.value) : 0;
                if (iid > 0) payload.id = iid;
                if (pathExtra) {
                    payload.image_path = pathExtra;
                } else if (itemImageUrl && itemImageUrl.value.trim()) {
                    payload.image_path = itemImageUrl.value.trim();
                }
                await window.SRMS.apiPostJson('api/menu-save', payload);
                await reloadAll();
                window.SRMS.toast('Menu item saved');
                if (itemReset) itemReset.click();
            } catch (err) {
                window.SRMS.toast(err.message || 'Save failed', true);
            }
        });
    }

    if (itemReset) {
        itemReset.addEventListener('click', function () {
            if (itemId) itemId.value = '';
            if (itemName) itemName.value = '';
            if (itemDesc) itemDesc.value = '';
            if (itemPrice) itemPrice.value = '';
            if (itemImage) itemImage.value = '';
            if (itemImageUrl) itemImageUrl.value = '';
            if (itemActive) itemActive.checked = true;
            if (itemPreview) itemPreview.innerHTML = '';
        });
    }

    async function reloadAll() {
        var c = await window.SRMS.apiFetch('api/categories');
        var m = await window.SRMS.apiFetch('api/menu?active=0');
        if (c.success) categories = c.data;
        if (m.success) {
            items = m.data.map(function (row) {
                return Object.assign({}, row, {
                    image_url:
                        row.image_url ||
                        (window.SRMS.resolveMenuImageUrl ? window.SRMS.resolveMenuImageUrl(row) : '') ||
                        null,
                });
            });
        }
        fillCategorySelect();
        renderCategories();
        renderItems();
    }

    fillCategorySelect();
    renderCategories();
    renderItems();
})();
