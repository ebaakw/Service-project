/**
 * Driver order scheduling suggestions in tenant timezone.
 * All date/time logic uses the given IANA timezone (from settings).
 */

/**
 * Get current date (Y-m-d) and time (HH:mm) in the given timezone.
 * @param {string} timezone - IANA timezone (e.g. Asia/Kuwait)
 * @returns {{ dateYmd: string, timeHm: string }}
 */
export function getNowInTimezone(timezone) {
    const d = new Date();
    const opts = { timeZone: timezone || 'UTC', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false };
    const parts = new Intl.DateTimeFormat('en-CA', opts).formatToParts(d);
    const get = (type) => parts.find((p) => p.type === type)?.value || '';
    const year = get('year');
    const month = get('month');
    const day = get('day');
    const hour = get('hour');
    const minute = get('minute');
    return {
        dateYmd: `${year}-${month}-${day}`,
        timeHm: `${hour.padStart(2, '0')}:${minute.padStart(2, '0')}`,
    };
}

/**
 * Compare time strings HH:mm (lexicographic order is valid for same-day times).
 */
function timeLessOrEqual(a, b) {
    return (a || '') <= (b || '');
}

/**
 * Suggest nearest pickup date + window.
 * Only suggests a window whose START is still in the future (driver has not left yet).
 * - First window today with start > now; if none, first window tomorrow.
 * @param {{ dateYmd: string, timeHm: string }} now - current date and time in tenant timezone
 * @param {Array<{from: string, to: string}>} pickupWindows - sorted by from
 * @returns {{ date: string, from: string, to: string } | null}
 */
export function suggestPickup(now, pickupWindows) {
    if (!pickupWindows || pickupWindows.length === 0) return null;
    const today = now.dateYmd;
    const nowTime = now.timeHm;

    // أول نافذة تبدأ بعد الآن اليوم (لا نقتَرح نافذة بدأت لأن السائق خرج)
    for (const w of pickupWindows) {
        if (w.from > nowTime) {
            return { date: today, from: w.from, to: w.to };
        }
    }
    // لا توجد نافذة اليوم تبدأ بعد الآن → أول نافذة غداً
    const first = pickupWindows[0];
    const tomorrow = addDaysToYmd(today, 1);
    return { date: tomorrow, from: first.from, to: first.to };
}

/**
 * Add N days to Y-m-d string (naive calendar, no timezone).
 */
function addDaysToYmd(ymd, days) {
    const d = new Date(ymd + 'T12:00:00Z');
    d.setUTCDate(d.getUTCDate() + days);
    const y = d.getUTCFullYear();
    const m = String(d.getUTCMonth() + 1).padStart(2, '0');
    const day = String(d.getUTCDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

/**
 * Get zone offset in ms at a given date (approximate: use "now" for DST).
 * Zone offset = (time in zone) - (time in UTC) for the same instant.
 */
function getZoneOffsetMs(timezone, referenceDate) {
    const d = referenceDate ? new Date(referenceDate + 'T12:00:00Z') : new Date();
    const opts = { timeZone: timezone || 'UTC', hour12: false, hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric' };
    const utcParts = new Intl.DateTimeFormat('en-CA', { ...opts, timeZone: 'UTC' }).formatToParts(d);
    const zoneParts = new Intl.DateTimeFormat('en-CA', { ...opts, timeZone: timezone }).formatToParts(d);
    const getNum = (parts, type) => parseInt(parts.find((p) => p.type === type)?.value || '0', 10);
    const utcHour = getNum(utcParts, 'hour');
    const utcMin = getNum(utcParts, 'minute');
    const utcDay = getNum(utcParts, 'day');
    const zoneHour = getNum(zoneParts, 'hour');
    const zoneMin = getNum(zoneParts, 'minute');
    const zoneDay = getNum(zoneParts, 'day');
    const utcMins = utcDay * 24 * 60 + utcHour * 60 + utcMin;
    const zoneMins = zoneDay * 24 * 60 + zoneHour * 60 + zoneMin;
    return (zoneMins - utcMins) * 60 * 1000;
}

/**
 * Parse (dateYmd, timeHm) as local time in timezone, return UTC ms.
 */
function toUtcMs(dateYmd, timeHm, timezone) {
    const [hour, min] = (timeHm || '00:00').split(':').map((s) => parseInt(s, 10) || 0);
    const naiveMs = new Date(dateYmd + 'T' + (timeHm || '00:00') + ':00.000Z').getTime();
    const offsetMs = getZoneOffsetMs(timezone, dateYmd);
    return naiveMs - offsetMs;
}

/**
 * Format UTC ms as (dateYmd, timeHm) in timezone.
 */
export function fromUtcMs(utcMs, timezone) {
    const d = new Date(utcMs);
    const opts = { timeZone: timezone || 'UTC', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false };
    const parts = new Intl.DateTimeFormat('en-CA', opts).formatToParts(d);
    const get = (type) => parts.find((p) => p.type === type)?.value || '00';
    const year = get('year');
    const month = get('month');
    const day = get('day');
    const hour = get('hour').padStart(2, '0');
    const minute = get('minute').padStart(2, '0');
    return { dateYmd: `${year}-${month}-${day}`, timeHm: `${hour}:${minute}` };
}

/**
 * Convert ISO date-time string to (dateYmd, timeHm) in the given timezone.
 * Used when pre-filling edit form from order (e.g. pickup_scheduled_at) so
 * date/time inputs show system timezone, not browser local.
 *
 * @param {string|null|undefined} iso - ISO 8601 string from API
 * @param {string} timezone - IANA timezone (e.g. Asia/Kuwait)
 * @returns {{ dateYmd: string, timeHm: string }}
 */
export function isoToDateAndTime(iso, timezone) {
    if (iso == null || iso === '') {
        return { dateYmd: '', timeHm: '' };
    }
    const ms = new Date(iso).getTime();
    if (Number.isNaN(ms)) {
        return { dateYmd: '', timeHm: '' };
    }
    return fromUtcMs(ms, timezone || 'UTC');
}

/**
 * Suggest delivery date + window: first delivery window with start >= delivery_earliest,
 * where delivery_earliest = pickup_date + pickup_time_to + processing_hours.
 * @param {string} pickupDateYmd
 * @param {string} pickupTimeTo
 * @param {number} processingHours
 * @param {Array<{from: string, to: string}>} deliveryWindows
 * @param {string} timezone
 * @returns {{ date: string, from: string, to: string } | null}
 */
export function suggestDelivery(pickupDateYmd, pickupTimeTo, processingHours, deliveryWindows, timezone) {
    if (!deliveryWindows || deliveryWindows.length === 0) return null;
    const tz = timezone || 'UTC';
    const deliveryEarliestUtc = toUtcMs(pickupDateYmd, pickupTimeTo, tz) + processingHours * 60 * 60 * 1000;
    const { dateYmd: startDate, timeHm: startTime } = fromUtcMs(deliveryEarliestUtc, tz);

    for (let day = 0; day < 8; day++) {
        const d = day === 0 ? startDate : addDaysToYmd(startDate, day);
        for (const w of deliveryWindows) {
            if (d < startDate) continue;
            if (d > startDate) return { date: d, from: w.from, to: w.to };
            if (timeLessOrEqual(startTime, w.from)) return { date: d, from: w.from, to: w.to };
            if (timeLessOrEqual(w.from, startTime) && timeLessOrEqual(startTime, w.to)) return { date: d, from: w.from, to: w.to };
        }
    }
    return null;
}

/**
 * Suggest nearest delivery date + window from "now" (for delivery_only mode, e.g. invoice scheduling).
 * Only suggests a window whose START is still in the future (same rule as suggestPickup).
 * - First window today with start > now; if none, first window tomorrow.
 * @param {{ dateYmd: string, timeHm: string }} now - current date and time in tenant timezone
 * @param {Array<{from: string, to: string}>} deliveryWindows - sorted by from
 * @returns {{ date: string, from: string, to: string } | null}
 */
export function suggestDeliveryFromNow(now, deliveryWindows) {
    if (!deliveryWindows || deliveryWindows.length === 0) return null;
    const today = now.dateYmd;
    const nowTime = now.timeHm;

    for (const w of deliveryWindows) {
        if (w.from > nowTime) {
            return { date: today, from: w.from, to: w.to };
        }
    }
    const first = deliveryWindows[0];
    const tomorrow = addDaysToYmd(today, 1);
    return { date: tomorrow, from: first.from, to: first.to };
}

/**
 * Parse windows from settings (JSON string or array).
 * @param {string|Array} raw
 * @returns {Array<{from: string, to: string}>}
 */
export function parseWindows(raw) {
    if (!raw) return [];
    if (Array.isArray(raw)) return raw.filter((w) => w && typeof w.from === 'string' && typeof w.to === 'string');
    if (typeof raw !== 'string') return [];
    try {
        const arr = JSON.parse(raw);
        return Array.isArray(arr) ? arr.filter((w) => w && typeof w.from === 'string' && typeof w.to === 'string') : [];
    } catch {
        return [];
    }
}
