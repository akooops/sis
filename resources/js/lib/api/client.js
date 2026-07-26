/**
 * Central fetch client for the JSON API.
 *
 * Handles: CSRF header, JSON/FormData bodies, the `{ status, message, data }`
 * response envelope (unwrapped to `data`), and error normalization. 422
 * validation errors are surfaced as `ApiError.errors` (a { field: message } map)
 * for form binding.
 *
 * Query params follow the spatie/laravel-query-builder contract:
 *   filter[field]=…, filter[search]=…, sort=field | -field, include=rel, per_page, page
 * Use `buildQuery()` / pass a params object to `get()` and nested `filter`
 * objects are serialized to `filter[key]` automatically.
 */

/** Normalized API error carrying HTTP status + the 422 field-error map. */
export class ApiError extends Error {
    constructor(message, { status = 0, errors = {}, data = null } = {}) {
        super(message || 'Request failed');
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors;
        this.data = data;
    }
}

/**
 * CSRF header for a request: the XSRF-TOKEN cookie first, the <meta> tag only as
 * a fallback.
 *
 * Laravel rewrites that cookie on EVERY response, whereas the meta tag is
 * printed once by the Blade shell and never again — an Inertia visit swaps the
 * page component without re-rendering <head>. So when a session expired and the
 * auth guard bounced you to the login page (a client-side swap, not a reload),
 * the meta tag still held the DEAD session's token while the server had already
 * issued a fresh one, and logging in answered 419 "CSRF token mismatch".
 * Reading the cookie is self-healing: it always belongs to the session the
 * server last issued.
 *
 * The two headers are mutually exclusive on purpose. Laravel's
 * getTokenFromRequest() reads X-CSRF-TOKEN first and only falls back to
 * X-XSRF-TOKEN, so sending a stale meta token alongside a good cookie would
 * reintroduce the bug. The cookie value is passed through verbatim (just
 * URL-decoded) — it is encrypted, and Laravel decrypts it on the way in.
 */
function csrfHeader() {
    const cookie = document.cookie
        .split('; ')
        .find((c) => c.startsWith('XSRF-TOKEN='))
        ?.split('=')
        .slice(1)
        .join('=');

    if (cookie) return { 'X-XSRF-TOKEN': decodeURIComponent(cookie) };

    const meta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    return meta ? { 'X-CSRF-TOKEN': meta } : {};
}

/**
 * Serialize a params object into a query string, expanding nested objects into
 * bracketed keys (`filter: { search: 'x' }` -> `filter[search]=x`). Empty
 * strings, null and undefined are dropped; arrays repeat the key.
 * @param {Record<string, any>} params
 */
export function buildQuery(params = {}) {
    const sp = new URLSearchParams();

    const append = (key, value) => {
        if (value === null || value === undefined || value === '') return;
        if (Array.isArray(value)) {
            value.forEach((v) => append(key, v));
        } else if (typeof value === 'object') {
            Object.entries(value).forEach(([k, v]) => append(`${key}[${k}]`, v));
        } else if (typeof value === 'boolean') {
            sp.append(key, value ? '1' : '0');
        } else {
            sp.append(key, value);
        }
    };

    Object.entries(params).forEach(([key, value]) => append(key, value));
    return sp.toString();
}

async function request(url, { method = 'GET', body, params, signal, headers = {} } = {}) {
    let target = url;
    if (params) {
        const qs = buildQuery(params);
        if (qs) target += (target.includes('?') ? '&' : '?') + qs;
    }

    const isFormData = body instanceof FormData;
    const finalHeaders = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...csrfHeader(),
        ...(body && !isFormData ? { 'Content-Type': 'application/json' } : {}),
        ...headers,
    };

    let response;
    try {
        response = await fetch(target, {
            method,
            signal,
            credentials: 'same-origin',
            headers: finalHeaders,
            body: isFormData ? body : body !== undefined ? JSON.stringify(body) : undefined,
        });
    } catch (err) {
        if (err?.name === 'AbortError') throw err;
        throw new ApiError('Network error. Please try again.', { status: 0 });
    }

    let payload = null;
    const text = await response.text();
    if (text) {
        try {
            payload = JSON.parse(text);
        } catch {
            payload = null;
        }
    }

    if (!response.ok) {
        throw new ApiError(payload?.message || `Request failed (${response.status})`, {
            status: response.status,
            errors: payload?.errors || {},
            data: payload?.data ?? null,
        });
    }

    // Unwrap the { status, message, data } envelope; fall back to raw payload.
    return payload && 'data' in payload ? payload.data : payload;
}

export const api = {
    get: (url, params, opts) => request(url, { method: 'GET', params, ...opts }),
    post: (url, body, opts) => request(url, { method: 'POST', body, ...opts }),
    put: (url, body, opts) => request(url, { method: 'PUT', body, ...opts }),
    patch: (url, body, opts) => request(url, { method: 'PATCH', body, ...opts }),
    delete: (url, opts) => request(url, { method: 'DELETE', ...opts }),
    request,
};
