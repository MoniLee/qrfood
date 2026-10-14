/**
 * Shared fetch helpers for SRMS (compiled companion: ../ts/srms-api.ts)
 */
(function (global) {
    'use strict';

    /** @param {string} msg */
    function toast(msg, isError) {
        var host = document.getElementById('toastHost');
        if (!host) {
            if (isError) console.error(msg);
            else console.log(msg);
            return;
        }
        var el = document.createElement('div');
        el.className = 'toast' + (isError ? ' toast-error' : '');
        el.textContent = msg;
        host.appendChild(el);
        window.setTimeout(function () {
            el.remove();
        }, 4500);
    }

    /**
     * @param {string} path e.g. api/stats
     * @param {RequestInit} [init]
     */
    async function apiFetch(path, init) {
        var base = (global.SRMS && global.SRMS.baseUrl) || '';
        var url = base + '/' + String(path).replace(/^\//, '');
        var headers = Object.assign(
            {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': (global.SRMS && global.SRMS.csrfToken) || '',
            },
            (init && init.headers) || {}
        );
        var res = await fetch(url, Object.assign({}, init, { headers: headers }));
        var text = await res.text();
        /** @type {unknown} */
        var data;
        try {
            data = text ? JSON.parse(text) : {};
        } catch (e) {
            throw new Error('Invalid server response');
        }
        if (res.status === 401 && data && typeof data === 'object' && data.redirect) {
            window.location.href = data.redirect;
            throw new Error('Unauthorized');
        }
        if (!res.ok) {
            var err =
                data && typeof data === 'object' && data.error
                    ? String(data.error)
                    : 'Request failed (' + res.status + ')';
            throw new Error(err);
        }
        return data;
    }

    /**
     * @param {string} path
     * @param {object} [body]
     */
    function apiPostJson(path, body) {
        return apiFetch(path, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: body ? JSON.stringify(body) : '{}',
        });
    }

    /**
     * Menu row from API: prefer image_url; support external URL in image_path.
     * @param {{ image_url?: string|null, image_path?: string|null }} it
     * @returns {string}
     */
    function resolveMenuImageUrl(it) {
        if (it.image_url) return String(it.image_url);
        var p = it.image_path;
        if (!p) return '';
        var s = String(p).trim();
        if (/^https?:\/\//i.test(s)) return s;
        var base = (global.SRMS && global.SRMS.baseUrl) || '';
        return base + '/frontend/' + s.replace(/^\//, '');
    }

    global.SRMS = global.SRMS || {};
    global.SRMS.toast = toast;
    global.SRMS.apiFetch = apiFetch;
    global.SRMS.apiPostJson = apiPostJson;
    global.SRMS.resolveMenuImageUrl = resolveMenuImageUrl;
    global.SRMS.api = {
        get: apiFetch,
        post: apiPostJson
    };
})(typeof window !== 'undefined' ? window : globalThis);
