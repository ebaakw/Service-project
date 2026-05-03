/**
 * Shared helpers for subscription/wallet payment links and WhatsApp (wa.me).
 * @param {string} phone
 * @param {string} message
 */
export function buildWhatsAppUrl(phone, message) {
    const raw = String(phone || '').trim();
    const digits = raw.replace(/[^\d+]/g, '');
    const text = encodeURIComponent(String(message || ''));
    return `https://wa.me/${encodeURIComponent(digits)}?text=${text}`;
}

/**
 * @param {string} purposeType
 * @param {function} __ translate function from useTranslate
 */
export function resolvePaymentLinkPurposeLabel(purposeType, __) {
    let v = String(purposeType || '');
    if (v.endsWith('_label')) {
        v = v.slice(0, -'_label'.length);
    }
    const map = {
        invoice_payment: __('tenant.payment_link_purpose_invoice_payment'),
        subscription_renewal: __('tenant.payment_link_purpose_subscription_renewal'),
        wallet_topup: __('tenant.payment_link_purpose_wallet_topup'),
    };
    return map[v] || v;
}

/**
 * @param {object} params
 * @param {string} params.url
 * @param {string} params.phone
 * @param {string} [params.name]
 * @param {string} [params.amount]
 * @param {string} [params.purposeType]
 * @param {string} [params.invoice_association_name]
 * @param {function} params.__
 * @returns {boolean} true if a window was opened
 */
export function openSubscriptionPaymentLinkWhatsApp({ url, phone, amount, purposeType, invoice_association_name, __ }) {
    const u = String(url || '').trim();
    if (!u) {
        return false;
    }
    const p = String(phone || '').trim();
    if (!p) {
        return false;
    }
    const typeLabel = resolvePaymentLinkPurposeLabel(purposeType, __);
    const assoc = String(invoice_association_name || '').trim();
    const opening = assoc
        ? __('tenant.payment_link_whatsapp_opening_named', { name: `*${assoc}*` })
        : __('tenant.payment_link_whatsapp_opening_generic');
    const rest = __('tenant.payment_link_whatsapp_rest', {
        amount: String(amount ?? ''),
        type: typeLabel,
        type_label: typeLabel,
        url: u,
    });
    const msg = `${opening}\n${rest}`;
    const wa = buildWhatsAppUrl(p, msg);
    window.open(wa, '_blank', 'noopener,noreferrer');
    return true;
}
