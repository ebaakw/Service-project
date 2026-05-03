/**
 * إذا كانت السلسلة لا تحتوي على إزاحة زمنية (Z أو ±HH:MM)، نعتبرها UTC
 * لأن قاعدة البيانات تخزّن بتوقيت UTC؛ وبهذا يتجنّب المتصفح تفسيرها كتوقيت جهاز المستخدم.
 */
function normalizeToUtcIfNeeded(iso) {
    if (typeof iso !== "string" || iso === "") return iso;
    const s = iso.trim();
    // تحتوي على Z أو إزاحة مثل +03:00 أو -05:00
    if (/Z$/i.test(s) || /[+-]\d{2}:?\d{2}$/.test(s)) return iso;
    // استبدال المسافة بـ T ثم إلحاق Z لاعتبارها UTC
    const withT = s.replace(" ", "T");
    return withT.includes("T") ? `${withT}Z` : `${withT}T00:00:00Z`;
}

/**
 * تنسيق التاريخ والوقت وفق توقيت النظام.
 * يُستخدم في كامل النظام لعرض أي تاريخ قادم من قاعدة البيانات أو من الـ API
 * لضمان اتساق التوقيت (إعدادات المستأجر: timezone).
 *
 * @param {string|Date|null|undefined} iso - تاريخ بصيغة ISO 8601 أو كائن Date
 * @param {string} [timezone='UTC'] - المنطقة الزمنية (مثل Asia/Kuwait أو من إعدادات النظام)
 * @param {{ dateOnly?: boolean, timeOnly?: boolean, locale?: string } | Intl.DateTimeFormatOptions} [options] - خيارات التنسيق
 * @returns {string} النص المُنسَّق أو سلسلة فارغة إذا لم يُمرَّر تاريخ صالح
 */
export function formatInTimezone(iso, timezone = "UTC", options = {}) {
    if (iso == null || iso === "") return "";
    const normalized = typeof iso === "string" ? normalizeToUtcIfNeeded(iso) : iso;
    const date = typeof normalized === "string" ? new Date(normalized) : normalized;
    if (Number.isNaN(date.getTime())) return "";

    // عرض التاريخ بتوقيت النظام (إعدادات المستأجر) وليس بتوقيت المتصفح؛ وإلا سيظهر توقيت خاطئ (مثلاً جهاز المستخدم UTC+2 والمنظومة Asia/Kuwait).
    const opts = { timeZone: timezone };

    if (options.dateOnly) {
        Object.assign(opts, {
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
        });
        return date.toLocaleString(options.locale ?? undefined, opts);
    }
    if (options.timeOnly) {
        Object.assign(opts, {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
        });
        return date.toLocaleString(options.locale ?? undefined, opts);
    }

    Object.assign(
        opts,
        {
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
        },
        options && typeof options === "object" && !options.dateOnly && !options.timeOnly ? options : {},
    );

    return date.toLocaleString(options.locale ?? undefined, opts);
}

/**
 * تنسيق تاريخ ووقت كامل (لتوقيت النظام).
 * @param {string|Date|null|undefined} iso
 * @param {string} [timezone='UTC']
 * @returns {string}
 */
export function formatDateTime(iso, timezone = "UTC") {
    return formatInTimezone(iso, timezone, {});
}

/**
 * تنسيق تاريخ فقط (بدون وقت).
 * @param {string|Date|null|undefined} iso
 * @param {string} [timezone='UTC']
 * @returns {string}
 */
export function formatDate(iso, timezone = "UTC") {
    return formatInTimezone(iso, timezone, { dateOnly: true });
}

/**
 * تنسيق وقت فقط.
 * @param {string|Date|null|undefined} iso
 * @param {string} [timezone='UTC']
 * @returns {string}
 */
export function formatTime(iso, timezone = "UTC") {
    return formatInTimezone(iso, timezone, { timeOnly: true });
}
