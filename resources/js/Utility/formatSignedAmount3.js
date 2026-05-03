/**
 * Display helper for wallet-style amounts: same scale as backend thousandthConversion (3 decimal places),
 * preserving the sign (positive = prepaid/top-up, negative = credit/debt).
 */
export function formatSignedAmount3(value) {
    const n = Number(value);
    if (!Number.isFinite(n)) {
        return "0.000";
    }
    return n.toFixed(3);
}
