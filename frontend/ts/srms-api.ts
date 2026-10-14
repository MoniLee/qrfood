/**
 * TypeScript source for shared API helpers.
 * The runtime bundle used by the app is `frontend/js/srms-api.js` (keep in sync when editing types here).
 */

export type JsonRecord = Record<string, unknown>;

export interface SrmsWindow extends Window {
    SRMS: {
        baseUrl: string;
        pollMs?: number;
        gstRate?: number;
        toast: (msg: string, isError?: boolean) => void;
        apiFetch: (path: string, init?: RequestInit) => Promise<JsonRecord>;
        apiPostJson: (path: string, body?: JsonRecord) => Promise<JsonRecord>;
        resolveMenuImageUrl: (it: { image_url?: string | null; image_path?: string | null }) => string;
    };
}

export async function apiFetch(path: string, init: RequestInit = {}): Promise<JsonRecord> {
    const g = window as unknown as SrmsWindow;
    const base = g.SRMS?.baseUrl ?? '';
    const url = `${base.replace(/\/$/, '')}/${path.replace(/^\//, '')}`;
    const headers: HeadersInit = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(init.headers ?? {}),
    };
    const res = await fetch(url, { ...init, headers });
    const text = await res.text();
    const data = (text ? JSON.parse(text) : {}) as JsonRecord;
    if (res.status === 401 && data.redirect) {
        window.location.href = String(data.redirect);
        throw new Error('Unauthorized');
    }
    if (!res.ok) {
        const err = typeof data.error === 'string' ? data.error : `Request failed (${res.status})`;
        throw new Error(err);
    }
    return data;
}

export function apiPostJson(path: string, body: JsonRecord = {}): Promise<JsonRecord> {
    return apiFetch(path, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    });
}
