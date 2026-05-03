const ARABIC_RE = /[\u0600-\u06FF]/;

const state = {
    loaded: false,
    loadingPromise: null,
    provinceArToEn: new Map(),
    areaArToEn: new Map(),
};

function safeStr(v) {
    return v == null ? '' : String(v).trim();
}

function isProbablyArabic(text) {
    return ARABIC_RE.test(safeStr(text));
}

function parseProvinceAr(province) {
    // `province` in cities.json often looks like: "Kuwait City,العاصمة"
    const s = safeStr(province);
    if (!s) return '';
    const parts = s.split(',');
    if (parts.length < 2) return '';
    return safeStr(parts.slice(1).join(','));
}

async function loadMapsOnce() {
    if (state.loaded) return;
    if (state.loadingPromise) return state.loadingPromise;

    state.loadingPromise = (async () => {
        const res = await fetch('/cities.json', {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!res.ok) {
            throw new Error(`Failed to fetch /cities.json (${res.status})`);
        }
        const json = await res.json();

        // Provinces mapping: `provinces[].ar_name` -> `provinces[].name`
        const provinces = Array.isArray(json?.provinces) ? json.provinces : [];
        for (const p of provinces) {
            const ar = safeStr(p?.ar_name);
            const en = safeStr(p?.name);
            if (ar && en) state.provinceArToEn.set(ar, en);
        }

        // Areas mapping: `branch_delivery_charges[].area_ar` -> `branch_delivery_charges[].area`
        // Also province mapping from `province`/`province_en` when available.
        const charges = Array.isArray(json?.branch_delivery_charges) ? json.branch_delivery_charges : [];
        for (const c of charges) {
            const areaAr = safeStr(c?.area_ar);
            const areaEn = safeStr(c?.area);
            if (areaAr && areaEn) state.areaArToEn.set(areaAr, areaEn);

            const provinceEn = safeStr(c?.province_en);
            const provinceAr = parseProvinceAr(c?.province);
            if (provinceAr && provinceEn) state.provinceArToEn.set(provinceAr, provinceEn);
        }

        state.loaded = true;
    })().finally(() => {
        state.loadingPromise = null;
    });

    return state.loadingPromise;
}

export async function ensureKuwaitLocationMaps() {
    try {
        await loadMapsOnce();
    } catch (_) {
        // If fetching fails, keep UI functional without throwing.
    }
}

export function getKuwaitProvinceLabel(option, locale) {
    const loc = safeStr(locale).toLowerCase();
    const isEn = loc && !loc.startsWith('ar');

    const name = safeStr(option?.name);
    const arName = safeStr(option?.ar_name);

    if (!isEn) return arName || name || '';

    // Prefer existing EN if it doesn't look Arabic.
    if (name && !isProbablyArabic(name)) return name;

    // Try mapping via Arabic label (ar_name first, then name if it's Arabic).
    const key = arName || (isProbablyArabic(name) ? name : '');
    if (key && state.provinceArToEn.has(key)) return state.provinceArToEn.get(key) || '';

    return name || arName || '';
}

export function getKuwaitAreaLabel(option, locale) {
    const loc = safeStr(locale).toLowerCase();
    const isEn = loc && !loc.startsWith('ar');

    const name = safeStr(option?.name);
    const arName = safeStr(option?.ar_name);

    if (!isEn) return arName || name || '';

    if (name && !isProbablyArabic(name)) return name;

    const key = arName || (isProbablyArabic(name) ? name : '');
    if (key && state.areaArToEn.has(key)) return state.areaArToEn.get(key) || '';

    return name || arName || '';
}

