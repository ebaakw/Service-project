const getBase = () => (typeof window !== 'undefined' ? window.location.origin : '');

/**
 * يضمن أن الرابط النهائي يكون ضمن نفس الـ origin الحالي (نفس النطاق والمنفذ)
 * لتجنب التنقل الخاطئ عند استخدام subdomains (مثل tenant.localhost vs localhost).
 */
function normalizeToCurrentOrigin(path) {
    if (typeof path !== 'string' || !path) return getBase() + '/';
    const base = getBase();
    if (path.startsWith('http://') || path.startsWith('https://')) {
        try {
            const u = new URL(path);
            return base + u.pathname + u.search + u.hash;
        } catch (_) {
            path = path.replace(/^https?:\/\/[^/]+/, '');
        }
    }
    const normalized = path.startsWith('/') ? path : '/' + path;
    try {
        new URL(normalized, base);
        return base + normalized;
    } catch (_) {
        return base + '/';
    }
}

/**
 * Returns a valid absolute URL for Inertia Link (same-origin).
 * Ziggy route() can return URLs with wrong host/port in multi-tenant setups; this forces current origin.
 */
export function routeUrl(name, params = {}) {
    let path = '';
    try {
        if (typeof route === 'function') {
            path = route(name, params);
        }
    } catch (_) {}
    return normalizeToCurrentOrigin(path);
}

/**
 * استخدمه في روابط Link بدل route() لضمان البقاء على نفس النطاق.
 * مثال: href={linkRoute('users.edit', item.id)}
 */
export function linkRoute(name, ...args) {
    let path = '';
    try {
        if (typeof route === 'function') {
            path = args.length > 0 ? route(name, ...args) : route(name);
        }
    } catch (_) {}
    return normalizeToCurrentOrigin(path);
}
