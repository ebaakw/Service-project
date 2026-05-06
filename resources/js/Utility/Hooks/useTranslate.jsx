const getByDot = (obj, key) => {
    if (!obj || typeof obj !== 'object' || !key) return undefined;
    if (Object.prototype.hasOwnProperty.call(obj, key)) return obj[key];
    return key.split('.').reduce((acc, part) => {
        if (!acc || typeof acc !== 'object') return undefined;
        return acc[part];
    }, obj);
};

const resolveTranslations = () => {
    if (typeof window === 'undefined') return {};

    // Prefer Inertia shared props (deterministic per response).
    const page = window.__inertia_page;
    const inertiaT = page?.props?.t;

    // Fallback to server-injected flattened translations (x-translations).
    const windowT = window._translations;

    return { inertiaT, windowT };
};

export const __ = (key, replacements = {}, fallback = undefined) => {
    const { inertiaT, windowT } = resolveTranslations();

    const fromInertia = getByDot(inertiaT, key);
    const fromWindow = getByDot(windowT, key);

    let translation =
        (typeof fromInertia === 'string' && fromInertia) ? fromInertia
            : (typeof fromWindow === 'string' && fromWindow) ? fromWindow
                : (typeof fallback === 'string' ? fallback : key);

    const keys = Object.keys(replacements).sort((a, b) => b.length - a.length);
    keys.forEach((r) => {
        const value = replacements[r];
        translation = translation.replaceAll(`:${r}`, value ?? '');
    });

    return translation;
}
