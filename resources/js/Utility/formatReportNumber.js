/**
 * Stable rounding for report display and footer sums (avoids long float strings).
 *
 * @param {unknown} value
 * @param {number} decimals
 * @returns {number}
 */
export function roundToDecimals(value, decimals) {
    const d = Math.max(0, Math.min(20, Math.floor(Number(decimals))));
    const n = Number(value);
    if (!Number.isFinite(n)) {
        return 0;
    }
    return Number(n.toFixed(d));
}

/**
 * @param {unknown} value
 * @param {number} decimals
 * @returns {string}
 */
export function formatReportDecimals(value, decimals) {
    const d = Math.max(0, Math.min(20, Math.floor(Number(decimals))));
    const n = Number(value);
    if (!Number.isFinite(n)) {
        return d === 0 ? "0" : `0.${"0".repeat(d)}`;
    }
    return n.toFixed(d);
}
