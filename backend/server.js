'use strict';

require('dotenv').config({ path: require('path').resolve(__dirname, '..', '.env') });

const crypto = require('node:crypto');
const fs = require('node:fs');
const path = require('node:path');
const express = require('express');
const session = require('express-session');
const bcrypt = require('bcryptjs');
const multer = require('multer');
const pool = require('./config/database');
const createOrderQrRoutes = require('./routes/orderQrRoutes');

const root = path.resolve(__dirname, '..');
const app = express();
const baseUrl = (process.env.BASE_URL || '').replace(/\/+$/, '');
if (process.env.NODE_ENV === 'production' && !process.env.SESSION_SECRET) {
    throw new Error('Set SESSION_SECRET before starting in production.');
}
const sessionSecret = process.env.SESSION_SECRET || crypto.randomBytes(32).toString('hex');
const gstRate = Number(process.env.GST_DEFAULT_RATE || 18);

app.disable('x-powered-by');
app.set('trust proxy', process.env.TRUST_PROXY === 'true' ? 1 : false);
app.set('view engine', 'ejs');
app.set('views', path.join(root, 'frontend', 'views'));
app.use(express.urlencoded({ extended: false, limit: '1mb' }));
app.use(express.json({ limit: '1mb' }));
app.use(session({
    name: 'SRMS_SESSION',
    secret: sessionSecret,
    resave: false,
    saveUninitialized: false,
    cookie: {
        httpOnly: true,
        sameSite: 'lax',
        secure: process.env.NODE_ENV === 'production',
        maxAge: 1000 * 60 * 60 * 12,
    },
}));
app.use('/frontend/views', (_req, res) => res.sendStatus(404));
app.use('/frontend', express.static(path.join(root, 'frontend'), { fallthrough: true, maxAge: '1h' }));
app.use((req, res, next) => {
    res.locals.baseUrl = baseUrl;
    res.locals.csrfToken = csrfToken(req);
    res.locals.user = req.session.user || null;
    res.locals.role = req.session.user?.role || 'guest';
    res.locals.current = (req.path.replace(baseUrl, '').split('/').filter(Boolean)[0]) || '';
    res.locals.title = 'Velvet Plate';
    res.locals.flashError = req.session.flashError || '';
    delete req.session.flashError;
    res.locals.gstRate = gstRate;
    res.locals.minimalLayout = ['/login', '/register'].includes(req.path);
    res.locals.extraScripts = [];
    res.locals.extraCss = null;
    res.locals.e = escapeHtml;
    res.locals.jsonForScript = jsonForScript;
    res.locals.assetUrl = (url) => `${baseUrl}/frontend/${String(url).replace(/^\/+/, '')}`;
    res.locals.appUrl = (url = '') => `${baseUrl}/${String(url).replace(/^\/+/, '')}`;
    next();
});

function csrfToken(req) {
    if (!req.session.csrf) req.session.csrf = crypto.randomBytes(32).toString('hex');
    return req.session.csrf;
}
function regenerateSession(req) {
    return new Promise((resolve, reject) => {
        req.session.regenerate((error) => error ? reject(error) : resolve());
    });
}
function jsonForScript(value) {
    return JSON.stringify(value).replace(/[<>&\u2028\u2029]/g, (char) => ({
        '<': '\\u003c', '>': '\\u003e', '&': '\\u0026', '\u2028': '\\u2028', '\u2029': '\\u2029',
    })[char]);
}
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[char]);
}
function safeText(value, max = 500) {
    return String(value ?? '').trim().slice(0, max);
}
function jsonError(res, message, status = 400) {
    return res.status(status).json({ success: false, error: message });
}
function apiAuth(roles) {
    return (req, res, next) => {
        const role = req.session.user?.role;
            if (!role) return res.status(401).json({ success: false, error: 'Unauthorized', redirect: `${baseUrl}/login` });
        if (roles && !roles.includes(role)) return jsonError(res, 'Forbidden', 403);
        next();
    };
}
function pageAuth(roles) {
    return (req, res, next) => {
        const role = req.session.user?.role;
        if (!role) return res.redirect(`${baseUrl}/login`);
        if (roles && !roles.includes(role)) return res.status(403).send('Forbidden');
        next();
    };
}
function guestOnly(req, res, next) {
    if (req.session.user) {
        const destination = { admin: 'dashboard', staff: 'pos', customer: 'menu', kitchen: 'kitchen' }[req.session.user.role] || 'menu';
        return res.redirect(`${baseUrl}/${destination}`);
    }
    next();
}
function view(res, template, data = {}) {
    return res.render(template, { ...res.locals, ...data });
}
async function query(sql, params = []) {
    const [rows] = await pool.execute(sql, params);
    return rows;
}
async function one(sql, params = []) {
    const rows = await query(sql, params);
    return rows[0] || null;
}
async function tableStats() {
    const rows = await query("SELECT status, COUNT(*) AS count FROM `tables` GROUP BY status");
    const counts = Object.fromEntries(rows.map((row) => [row.status, Number(row.count)]));
    return { occupied: counts.occupied || 0, available: counts.available || 0 };
}
async function menuItems(activeOnly = false) {
    const condition = activeOnly ? ' WHERE m.is_active = 1' : '';
    return query(`SELECT m.*, c.name AS category_name FROM menu_items m
        JOIN categories c ON c.id = m.category_id${condition} ORDER BY c.sort_order, m.name`);
}
function menuWithImage(rows) {
    return rows.map((row) => ({
        ...row,
        image_url: row.image_path
            ? (/^https?:\/\//i.test(row.image_path) ? row.image_path : `${baseUrl}/frontend/${row.image_path.replace(/^\/+/, '')}`)
            : null,
    }));
}
async function categories() {
    return query('SELECT * FROM categories ORDER BY sort_order, name');
}
async function orderById(id) {
    return one('SELECT o.*, t.label AS table_label FROM orders o LEFT JOIN `tables` t ON t.id=o.table_id WHERE o.id=?', [id]);
}
async function orderItems(id) {
    return query(`SELECT oi.*, m.name AS item_name FROM order_items oi
        JOIN menu_items m ON m.id=oi.menu_item_id WHERE oi.order_id=? ORDER BY oi.id`, [id]);
}
async function recentOrders(limit = 100, userId = null) {
    if (userId) return query(`SELECT o.*, t.label AS table_label FROM orders o LEFT JOIN \`tables\` t ON t.id=o.table_id
        WHERE o.user_id=? ORDER BY o.created_at DESC LIMIT ?`, [userId, limit]);
    return query(`SELECT o.*, t.label AS table_label FROM orders o LEFT JOIN \`tables\` t ON t.id=o.table_id
        ORDER BY o.created_at DESC LIMIT ?`, [limit]);
}
async function setOrderStatus(id, status) {
    const order = await orderById(id);
    if (!order) return false;
    await query('UPDATE orders SET status=? WHERE id=?', [status, id]);
    if (order.status !== 'completed' && status === 'completed' && order.type === 'DINE_IN' && order.table_id) {
        await query("UPDATE `tables` SET status='available' WHERE id=?", [order.table_id]);
    }
    return true;
}
async function dashboardStats() {
    const stats = await one(`SELECT COUNT(*) AS total_orders,
        COALESCE(SUM(CASE WHEN status='completed' THEN total ELSE 0 END),0) AS revenue,
        SUM(DATE(created_at)=CURDATE()) AS orders_today,
        COALESCE(SUM(CASE WHEN DATE(created_at)=CURDATE() AND status='completed' THEN total ELSE 0 END),0) AS revenue_today
        FROM orders`);
    const tables = await tableStats();
    const popular = await query(`SELECT oi.menu_item_id, SUM(oi.quantity) AS total_qty, m.name
        FROM order_items oi JOIN menu_items m ON m.id=oi.menu_item_id JOIN orders o ON o.id=oi.order_id
        WHERE o.status='completed' GROUP BY oi.menu_item_id, m.name ORDER BY total_qty DESC LIMIT 8`);
    const statusCounts = await query('SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status');
    const revenueByDay = await query(`SELECT DATE(created_at) AS day, COALESCE(SUM(total),0) AS total
        FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status='completed'
        GROUP BY DATE(created_at) ORDER BY day`);
    return {
        ...stats,
        total_orders: Number(stats.total_orders),
        orders_today: Number(stats.orders_today || 0),
        tables_occupied: tables.occupied,
        tables_available: tables.available,
        popular, status_counts: statusCounts, revenue_by_day: revenueByDay,
    };
}
function csrfForm(req, res, next) {
    const token = req.body?.csrf_token;
    const provided = Buffer.from(String(token || ''));
    const expected = Buffer.from(String(req.session.csrf || ''));
    if (!provided.length || provided.length !== expected.length || !crypto.timingSafeEqual(provided, expected)) {
        req.session.flashError = 'Invalid session. Please try again.';
        return res.redirect(`${baseUrl}/${req.path.slice(1)}`);
    }
    next();
}
function csrfJson(req, res, next) {
    const provided = Buffer.from(String(req.get('x-csrf-token') || ''));
    const expected = Buffer.from(String(req.session.csrf || ''));
    if (!provided.length || provided.length !== expected.length || !crypto.timingSafeEqual(provided, expected)) {
        return jsonError(res, 'Invalid session. Refresh the page and try again.', 403);
    }
    next();
}
function locationSchemaError(error, res) {
    if (error.code === 'ER_NO_SUCH_TABLE') return jsonError(res, 'Location tables are not installed. Import database/location_schema.sql.', 503);
    throw error;
}

const uploadDir = path.join(root, 'frontend', 'assets', 'uploads', 'menu');
fs.mkdirSync(uploadDir, { recursive: true });
const upload = multer({
    storage: multer.diskStorage({
        destination: uploadDir,
        filename: (_req, file, cb) => {
            const ext = { 'image/jpeg': '.jpg', 'image/png': '.png', 'image/webp': '.webp', 'image/gif': '.gif' }[file.mimetype] || '';
            cb(null, `menu_${crypto.randomBytes(8).toString('hex')}${ext}`);
        },
    }),
    limits: { fileSize: Number(process.env.MAX_UPLOAD_BYTES || 2 * 1024 * 1024) },
    fileFilter: (_req, file, cb) => {
        if (!['image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(file.mimetype)) return cb(new Error('Invalid image type'));
        cb(null, true);
    },
});

app.get('/', async (_req, res, next) => {
    try { view(res, 'pages/home', { title: 'Welcome | Velvet Plate', featured: menuWithImage((await menuItems(true)).slice(0, 6)) }); }
    catch (error) { next(error); }
});
app.get('/login', guestOnly, (req, res) => view(res, 'pages/login', { title: 'Sign in | Velvet Plate' }));
app.post('/login', guestOnly, csrfForm, async (req, res, next) => {
    try {
        const email = safeText(req.body.email, 128).toLowerCase();
        const user = await one('SELECT * FROM users WHERE email=? LIMIT 1', [email]);
        const hash = user?.password_hash?.replace(/^\$2y\$/, '$2b$');
        if (!user || !await bcrypt.compare(String(req.body.password || ''), hash || '')) {
            req.session.flashError = 'Invalid credentials.';
            return res.redirect(`${baseUrl}/login`);
        }
        await regenerateSession(req);
        req.session.user = { id: user.id, email: user.email, role: user.role, full_name: user.full_name };
        const destination = { admin: 'dashboard', staff: 'pos', customer: 'menu', kitchen: 'kitchen' }[user.role] || 'menu';
        res.redirect(`${baseUrl}/${destination}`);
    } catch (error) { next(error); }
});
app.get('/register', guestOnly, (_req, res) => view(res, 'pages/register', { title: 'Create account | Velvet Plate' }));
app.post('/register', guestOnly, csrfForm, async (req, res, next) => {
    try {
        const email = safeText(req.body.email, 128).toLowerCase();
        const name = safeText(req.body.full_name, 128);
        const password = String(req.body.password || '');
        if (!email || !name || password.length < 6) {
            req.session.flashError = 'All fields are required. Password must be at least 6 characters.';
            return res.redirect(`${baseUrl}/register`);
        }
        if (await one('SELECT id FROM users WHERE email=?', [email])) {
            req.session.flashError = 'Email is already registered.';
            return res.redirect(`${baseUrl}/register`);
        }
        const result = await query('INSERT INTO users (email,password_hash,role,full_name) VALUES (?,?,?,?)', [email, await bcrypt.hash(password, 10), 'customer', name]);
        await regenerateSession(req);
        req.session.user = { id: result.insertId, email, role: 'customer', full_name: name };
        res.redirect(`${baseUrl}/menu`);
    } catch (error) { next(error); }
});
app.get('/logout', (req, res) => req.session.destroy(() => res.redirect(`${baseUrl}/login`)));
app.get('/menu', async (_req, res, next) => {
    try {
        const tables = await query('SELECT * FROM `tables` ORDER BY label');
        const menu = menuWithImage(await menuItems(true));
        view(res, 'pages/public_menu', { title: 'Menu | Velvet Plate', extraScripts: ['public_menu.js'], tables, menu, categories: await categories() });
    } catch (error) { next(error); }
});
app.use('/qr', createOrderQrRoutes(pageAuth(['admin'])));
app.get('/dashboard', pageAuth(['admin']), async (_req, res, next) => {
    try {
        const stats = await dashboardStats();
        view(res, 'pages/dashboard', {
            title: 'Dashboard | Velvet Plate', extraScripts: ['dashboard.js'], stats, popular: stats.popular,
            statusCounts: stats.status_counts, chartRevenue: stats.revenue_by_day,
            dashPayload: { revenueByDay: stats.revenue_by_day, statusCounts: stats.status_counts, popular: stats.popular },
        });
    }
    catch (error) { next(error); }
});
app.get('/kitchen', pageAuth(['admin', 'kitchen']), (_req, res) => view(res, 'pages/kitchen', { title: 'Kitchen | Velvet Plate', extraScripts: ['kitchen.js'] }));
app.get('/pos', pageAuth(['admin', 'staff']), async (_req, res, next) => {
    try {
        const tables = await query('SELECT * FROM `tables` ORDER BY label');
        const menuInitial = menuWithImage(await menuItems(true));
        const stats = await one("SELECT COUNT(*) AS orders, COALESCE(SUM(CASE WHEN status='completed' THEN total ELSE 0 END),0) AS revenue FROM orders WHERE DATE(created_at)=CURDATE()");
        view(res, 'pages/pos', { title: 'Point of sale | Velvet Plate', extraScripts: ['pos.js'], tables, categories: await categories(), menuInitial, stats: { ...stats, tables_occ: (await tableStats()).occupied } });
    } catch (error) { next(error); }
});
app.get('/orders', pageAuth(['admin', 'staff', 'customer']), async (req, res, next) => {
    try {
        const customer = req.session.user.role === 'customer';
        view(res, 'pages/orders', { title: 'Orders | Velvet Plate', extraScripts: ['orders.js'], orders: await recentOrders(customer ? 50 : 80, customer ? req.session.user.id : null) });
    } catch (error) { next(error); }
});
app.get('/tables', pageAuth(['admin', 'staff']), async (_req, res, next) => {
    try { view(res, 'pages/tables', { title: 'Tables | Velvet Plate', extraScripts: ['tables.js'], tables: await query('SELECT * FROM `tables` ORDER BY label') }); }
    catch (error) { next(error); }
});
app.get('/manage-menu', pageAuth(['admin']), async (_req, res, next) => {
    try {
        const items = await menuItems(false);
        const cats = await categories();
        const itemsPayload = items.map((item) => ({ ...item, image_url: menuWithImage([item])[0].image_url }));
        view(res, 'pages/menu_manage', { title: 'Menu management | Velvet Plate', extraScripts: ['menu_manage.js'], items, categories: cats, itemsPayload, catsPayload: cats });
    }
    catch (error) { next(error); }
});
app.get('/invoice/:id', pageAuth(['admin', 'staff', 'customer']), async (req, res, next) => {
    try {
        const order = await orderById(Number(req.params.id));
        if (!order) return res.status(404).send('Order not found');
        if (req.session.user.role === 'customer' && Number(order.user_id) !== Number(req.session.user.id)) return res.status(403).send('You do not have access to this invoice.');
        view(res, 'pages/invoice', { title: `Invoice ${order.order_code} | Velvet Plate`, order, items: await orderItems(order.id) });
    } catch (error) { next(error); }
});

app.get('/api/stats', apiAuth(['admin']), async (_req, res, next) => {
    try { res.json({ success: true, data: await dashboardStats() }); } catch (error) { next(error); }
});
app.get('/api/menu', apiAuth(), async (req, res, next) => {
    try { res.json({ success: true, data: menuWithImage(await menuItems(req.query.active !== '0')) }); } catch (error) { next(error); }
});
app.get('/api/categories', apiAuth(), async (_req, res, next) => {
    try { res.json({ success: true, data: await categories() }); } catch (error) { next(error); }
});
app.post('/api/menu-save', apiAuth(['admin']), async (req, res, next) => {
    try {
        const id = Number(req.body.id || 0), categoryId = Number(req.body.category_id), name = safeText(req.body.name, 128), price = Number(req.body.price);
        if (!name || !Number.isFinite(price) || price < 0 || !await one('SELECT id FROM categories WHERE id=?', [categoryId])) return jsonError(res, 'Invalid menu data');
        let imagePath = req.body.image_path ? safeText(req.body.image_path, 512) : null;
        if (imagePath && !/^https?:\/\//i.test(imagePath) && imagePath.includes('..')) return jsonError(res, 'Invalid image path');
        const values = [categoryId, name, safeText(req.body.description, 2000), price, imagePath, req.body.is_active ? 1 : 0];
        if (id) {
            if (!imagePath) imagePath = (await one('SELECT image_path FROM menu_items WHERE id=?', [id]))?.image_path || null;
            values[4] = imagePath;
            await query('UPDATE menu_items SET category_id=?,name=?,description=?,price=?,image_path=?,is_active=? WHERE id=?', [...values, id]);
        } else {
            const result = await query('INSERT INTO menu_items (category_id,name,description,price,image_path,is_active) VALUES (?,?,?,?,?,?)', values);
            return res.json({ success: true, id: result.insertId });
        }
        res.json({ success: true, id });
    } catch (error) { next(error); }
});
app.post('/api/menu-delete', apiAuth(['admin']), async (req, res, next) => {
    try {
        const id = Number(req.body.id);
        if (!id) return jsonError(res, 'Invalid id');
        const item = await one('SELECT image_path FROM menu_items WHERE id=?', [id]);
        if (item?.image_path && !/^https?:\/\//i.test(item.image_path)) {
            const file = path.join(uploadDir, path.basename(item.image_path));
            if (file.startsWith(uploadDir)) fs.rm(file, { force: true }, () => {});
        }
        await query('DELETE FROM menu_items WHERE id=?', [id]);
        res.json({ success: true });
    } catch (error) { next(error); }
});
app.post('/api/menu-upload', apiAuth(['admin']), (req, res, next) => upload.single('image')(req, res, (error) => {
    if (error) return jsonError(res, error.code === 'LIMIT_FILE_SIZE' ? 'File too large' : error.message, 400);
    if (!req.file) return jsonError(res, 'Upload failed');
    fs.readFile(req.file.path, (readError, buffer) => {
        const valid = buffer && (
            (req.file.mimetype === 'image/jpeg' && buffer[0] === 0xff && buffer[1] === 0xd8 && buffer[2] === 0xff) ||
            (req.file.mimetype === 'image/png' && buffer.subarray(0, 8).equals(Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]))) ||
            (req.file.mimetype === 'image/gif' && /^GIF8[79]a/.test(buffer.subarray(0, 6).toString())) ||
            (req.file.mimetype === 'image/webp' && buffer.subarray(0, 4).toString() === 'RIFF' && buffer.subarray(8, 12).toString() === 'WEBP')
        );
        if (readError || !valid) {
            fs.rm(req.file.path, { force: true }, () => {});
            return jsonError(res, 'Invalid image type', 400);
        }
        res.json({ success: true, path: `assets/uploads/menu/${req.file.filename}` });
    });
}));
app.post('/api/category-save', apiAuth(['admin']), async (req, res, next) => {
    try {
        const id = Number(req.body.id || 0), name = safeText(req.body.name, 64), sort = Number(req.body.sort_order || 0);
        if (!name) return jsonError(res, 'Category name required');
        if (id) await query('UPDATE categories SET name=?,sort_order=? WHERE id=?', [name, sort, id]);
        else {
            const result = await query('INSERT INTO categories (name,sort_order) VALUES (?,?)', [name, sort]);
            return res.json({ success: true, id: result.insertId });
        }
        res.json({ success: true, id });
    } catch (error) { next(error); }
});
app.post('/api/category-delete', apiAuth(['admin']), async (req, res, next) => {
    try {
        const id = Number(req.body.id);
        if (!id) return jsonError(res, 'Invalid id');
        if (Number((await one('SELECT COUNT(*) AS count FROM menu_items WHERE category_id=?', [id])).count)) return jsonError(res, 'Cannot delete category with menu items');
        await query('DELETE FROM categories WHERE id=?', [id]);
        res.json({ success: true });
    } catch (error) { next(error); }
});
app.get('/api/tables', apiAuth(), async (_req, res, next) => {
    try { res.json({ success: true, data: await query('SELECT * FROM `tables` ORDER BY label') }); } catch (error) { next(error); }
});
app.post('/api/table-save', apiAuth(['admin']), async (req, res, next) => {
    try {
        const id = Number(req.body.id || 0), label = safeText(req.body.label, 32), capacity = Number(req.body.capacity || 4);
        const status = ['available', 'occupied'].includes(req.body.status) ? req.body.status : 'available';
        if (!label || capacity < 1) return jsonError(res, 'Invalid table data');
        if (id) await query('UPDATE `tables` SET label=?,capacity=?,status=? WHERE id=?', [label, capacity, status, id]);
        else {
            const result = await query('INSERT INTO `tables` (label,capacity,status) VALUES (?,?,?)', [label, capacity, status]);
            return res.json({ success: true, id: result.insertId });
        }
        res.json({ success: true, id });
    } catch (error) { next(error); }
});
app.post('/api/table-delete', apiAuth(['admin']), async (req, res, next) => {
    try {
        const id = Number(req.body.id);
        if (!id) return jsonError(res, 'Invalid id');
        await query('DELETE FROM `tables` WHERE id=?', [id]);
        res.json({ success: true });
    } catch (error) { next(error); }
});
app.post('/api/table-status', apiAuth(['admin', 'staff']), async (req, res, next) => {
    try {
        const id = Number(req.body.id), status = req.body.status;
        if (!id || !['available', 'occupied'].includes(status)) return jsonError(res, 'Invalid request');
        await query('UPDATE `tables` SET status=? WHERE id=?', [status, id]);
        res.json({ success: true });
    } catch (error) { next(error); }
});
app.post('/api/order-place', csrfJson, async (req, res, next) => {
    const conn = await pool.getConnection();
    try {
        const { type, cart } = req.body;
        if (!['DINE_IN', 'DELIVERY'].includes(type)) return jsonError(res, 'Invalid order type');
        if (!Array.isArray(cart) || !cart.length || cart.length > 100) return jsonError(res, 'Cart is empty');
        let tableId = null, name = null, phone = null, address = null;
        if (type === 'DINE_IN') {
            tableId = Number(req.body.table_id);
            if (!tableId || !await one('SELECT id FROM `tables` WHERE id=?', [tableId])) return jsonError(res, 'Select a valid table for dine-in');
            name = safeText(req.body.customer_name, 128) || null;
            phone = safeText(req.body.customer_phone, 32) || null;
        } else {
            name = safeText(req.body.customer_name, 128); phone = safeText(req.body.customer_phone, 32); address = safeText(req.body.delivery_address, 500);
            if (!name || !phone || !address) return jsonError(res, 'Name, phone, and address are required for delivery');
        }
        const lines = [];
        let subtotal = 0;
        for (const line of cart) {
            const menuId = Number(line.menu_item_id), qty = Number(line.quantity);
            if (!Number.isInteger(menuId) || !Number.isInteger(qty) || menuId < 1 || qty < 1 || qty > 100) return jsonError(res, 'Invalid cart line');
            const item = await one('SELECT id,price,is_active FROM menu_items WHERE id=?', [menuId]);
            if (!item || !item.is_active) return jsonError(res, `Item unavailable: ${menuId}`);
            const unitPrice = Number(item.price), lineTotal = Math.round(unitPrice * qty * 100) / 100;
            subtotal += lineTotal;
            lines.push([menuId, qty, unitPrice, lineTotal]);
        }
        subtotal = Math.round(subtotal * 100) / 100;
        const tax = Math.round(subtotal * gstRate) / 100;
        const total = Math.round((subtotal + tax) * 100) / 100;
        const code = `ORD-${crypto.randomBytes(3).toString('hex').toUpperCase()}`;
        await conn.beginTransaction();
        const [created] = await conn.execute(`INSERT INTO orders
            (order_code,type,user_id,table_id,customer_name,customer_phone,delivery_address,status,subtotal,gst_rate,gst_amount,total)
            VALUES (?,?,?,?,?,?,?,'pending',?,?,?,?)`,
        [code, type, req.session.user?.id || null, tableId, name, phone, address, subtotal, gstRate, tax, total]);
        for (const line of lines) await conn.execute('INSERT INTO order_items (order_id,menu_item_id,quantity,unit_price,line_total) VALUES (?,?,?,?,?)', [created.insertId, ...line]);
        if (type === 'DINE_IN') await conn.execute("UPDATE `tables` SET status='occupied' WHERE id=?", [tableId]);
        await conn.commit();
        res.json({ success: true, order_id: created.insertId, order_code: code });
    } catch (error) {
        await conn.rollback().catch(() => {});
        next(error);
    } finally { conn.release(); }
});
app.get('/api/order-list', apiAuth(['admin', 'staff', 'customer']), async (req, res, next) => {
    try {
        const limit = Math.min(200, Math.max(1, Number(req.query.limit) || 100));
        const userId = req.session.user.role === 'customer' ? req.session.user.id : null;
        res.json({ success: true, data: await recentOrders(limit, userId) });
    } catch (error) { next(error); }
});
app.post('/api/order-status', apiAuth(['admin', 'staff']), async (req, res, next) => {
    try {
        const id = Number(req.body.order_id), status = req.body.status;
        if (!id || !['pending', 'preparing', 'ready', 'completed'].includes(status)) return jsonError(res, 'Invalid request');
        await setOrderStatus(id, status);
        res.json({ success: true });
    } catch (error) { next(error); }
});
app.get('/api/kitchen-orders', apiAuth(['admin', 'kitchen']), async (_req, res, next) => {
    try {
        const orders = await query("SELECT o.*, t.label AS table_label FROM orders o LEFT JOIN `tables` t ON t.id=o.table_id WHERE o.status IN ('pending','preparing','ready') ORDER BY o.created_at");
        const data = await Promise.all(orders.map(async (order) => ({ order, items: await orderItems(order.id) })));
        res.json({ success: true, data, server_time: new Date().toISOString() });
    } catch (error) { next(error); }
});
app.post('/api/kitchen-status', apiAuth(['admin', 'kitchen']), async (req, res, next) => {
    try {
        const id = Number(req.body.order_id), status = req.body.status;
        if (!id || !['pending', 'preparing', 'ready', 'completed'].includes(status)) return jsonError(res, 'Invalid order or status');
        await setOrderStatus(id, status);
        res.json({ success: true });
    } catch (error) { next(error); }
});
app.get('/api/location-addresses', async (req, res, next) => {
    if (!req.session.user) return res.json({ success: true, data: [] });
    try { res.json({ success: true, data: await query('SELECT * FROM user_addresses WHERE user_id=? ORDER BY is_default DESC,created_at DESC', [req.session.user.id]) }); }
    catch (error) { if (error.code === 'ER_NO_SUCH_TABLE') locationSchemaError(error, res); else next(error); }
});
app.post('/api/location-save', apiAuth(), async (req, res, next) => {
    try {
        const d = req.body, userId = req.session.user.id;
        const required = ['full_name', 'phone_number', 'street_address', 'city', 'pincode'];
        if (required.some((key) => !safeText(d[key], 500))) return jsonError(res, 'Required fields missing');
        if (d.is_default) await query('UPDATE user_addresses SET is_default=0 WHERE user_id=?', [userId]);
        const values = [d.address_type || 'HOME', safeText(d.full_name, 128), safeText(d.phone_number, 32), safeText(d.flat_number, 64), safeText(d.street_address, 2000), safeText(d.landmark, 128), safeText(d.city, 64), safeText(d.state, 64), safeText(d.pincode, 16), d.is_default ? 1 : 0];
        if (d.id) await query(`UPDATE user_addresses SET address_type=?,full_name=?,phone_number=?,flat_number=?,street_address=?,landmark=?,city=?,state=?,pincode=?,is_default=? WHERE id=? AND user_id=?`, [...values, Number(d.id), userId]);
        else await query(`INSERT INTO user_addresses (address_type,full_name,phone_number,flat_number,street_address,landmark,city,state,pincode,is_default,user_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)`, [...values, userId]);
        res.json({ success: true });
    } catch (error) { if (error.code === 'ER_NO_SUCH_TABLE') locationSchemaError(error, res); else next(error); }
});
app.get('/api/location-check', async (req, res, next) => {
    try {
        const pincode = safeText(req.query.pincode, 16);
        if (!pincode) return jsonError(res, 'Pincode required');
        const zone = await one("SELECT * FROM location_zones WHERE is_active=1 AND FIND_IN_SET(?,REPLACE(pincodes,' ',''))", [pincode]);
        if (zone) return res.json({ success: true, available: true, data: { zone_name: zone.name, delivery_fee: Number(zone.delivery_fee), min_order: Number(zone.min_order), estimated_time: zone.estimated_time } });
        res.json({ success: true, available: false, message: 'Coming soon to your location!' });
    } catch (error) { if (error.code === 'ER_NO_SUCH_TABLE') locationSchemaError(error, res); else next(error); }
});
app.get('/api/location-geocode', (req, res) => {
    if (!req.query.lat || !req.query.lng) return jsonError(res, 'Lat/Lng required');
    res.json({ success: true, data: { full_address: 'Sample Street 123, Central Area, City, 110001', pincode: '110001', city: 'Delhi', state: 'Delhi', area: 'Cannaught Place' } });
});
app.get('/api/zone-list', apiAuth(['admin']), async (_req, res, next) => {
    try { res.json({ success: true, data: await query('SELECT * FROM location_zones ORDER BY created_at DESC') }); } catch (error) { if (error.code === 'ER_NO_SUCH_TABLE') locationSchemaError(error, res); else next(error); }
});
app.post('/api/zone-save', apiAuth(['admin']), async (req, res, next) => {
    try {
        const d = req.body;
        if (!safeText(d.name, 128) || !safeText(d.pincodes, 1000)) return jsonError(res, 'Name and pincodes are required');
        const values = [safeText(d.name, 128), safeText(d.pincodes, 1000), Number(d.delivery_fee || 0), Number(d.min_order || 0), safeText(d.estimated_time || '30-45 mins', 32), d.is_active ? 1 : 0];
        if (d.id) await query('UPDATE location_zones SET name=?,pincodes=?,delivery_fee=?,min_order=?,estimated_time=?,is_active=? WHERE id=?', [...values, Number(d.id)]);
        else await query('INSERT INTO location_zones (name,pincodes,delivery_fee,min_order,estimated_time,is_active) VALUES (?,?,?,?,?,?)', values);
        res.json({ success: true });
    } catch (error) { if (error.code === 'ER_NO_SUCH_TABLE') locationSchemaError(error, res); else next(error); }
});
app.get('/api/zone-delete', apiAuth(['admin']), async (req, res, next) => {
    try {
        if (!req.query.id) return jsonError(res, 'ID required');
        await query('DELETE FROM location_zones WHERE id=?', [Number(req.query.id)]);
        res.json({ success: true });
    } catch (error) { if (error.code === 'ER_NO_SUCH_TABLE') locationSchemaError(error, res); else next(error); }
});

app.use((req, res) => {
    if (req.path.startsWith('/api/')) return jsonError(res, 'Not found', 404);
    res.status(404).render('pages/not-found', { ...res.locals, title: 'Not found | Velvet Plate' });
});
app.use((error, req, res, _next) => {
    console.error(`${req.method} ${req.originalUrl}:`, error.message);
    if (res.headersSent) return;
    if (req.path.startsWith('/api/')) return jsonError(res, process.env.NODE_ENV === 'production' ? 'Server error' : error.message, error.status || 500);
    res.status(error.status || 500).send(process.env.NODE_ENV === 'production' ? 'Server error' : `Server error: ${escapeHtml(error.message)}`);
});

const port = Number(process.env.PORT || 3000);
if (require.main === module) {
    const host = process.env.HOST || '0.0.0.0';
    app.listen(port, host, () => {
        console.log(`Velvet Plate listening on port ${port} (local: http://127.0.0.1:${port})`);
    });
}
module.exports = app;
