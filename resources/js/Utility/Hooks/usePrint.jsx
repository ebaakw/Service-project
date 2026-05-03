import { __ } from "@/Utility/Hooks/useTranslate.jsx";

const escapeHtml = (str) => {
    if (str == null) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
};

const formatBalance = (value) => {
    if (value == null || value === '') return '0.000';
    const n = Number(value);
    if (Number.isNaN(n)) return '0.000';
    const s = n < 0 ? '-' + String(Math.abs(n).toFixed(3)) : String(n.toFixed(3));
    return s;
};

/**
 * Writes receipt HTML for package/top-up (with balance before/after) into a hidden iframe and triggers print (no new tab).
 * @param {Object} receiptData - From backend flash: receipt_type, client_name, client_phone, balance_before, balance_after, amount_added, occurred_at_display, payment_method, payment_reference, payment_method_label, branch_*, association_name, logo_url, cashier_name, package_name (if package).
 */
export const printSubscriptionReceipt = (receiptData, delay = 200) => {
    if (!receiptData || typeof receiptData !== 'object') return;

    const isPackage = receiptData.receipt_type === 'package';
    const docDir = typeof document !== 'undefined' ? (document.documentElement.getAttribute('dir') || '') : '';
    const docLang = typeof document !== 'undefined' ? (document.documentElement.getAttribute('lang') || '') : '';
    const isRtl = (docDir || '').toLowerCase() === 'rtl' || (!docDir && docLang.toLowerCase() === 'ar');
    const htmlDir = isRtl ? 'rtl' : 'ltr';
    const htmlLang = docLang || (isRtl ? 'ar' : 'en');
    const bodyDirection = isRtl ? 'rtl' : 'ltr';
    const bodyTextAlign = isRtl ? 'right' : 'left';
    const title = isPackage
        ? (__('tenant.receipt_package_title') !== 'tenant.receipt_package_title' ? __('tenant.receipt_package_title') : 'إيصال إضافة باقة')
        : (__('tenant.receipt_topup_title') !== 'tenant.receipt_topup_title' ? __('tenant.receipt_topup_title') : 'إيصال تعبئة رصيد');
    const balanceBeforeLabel = __('tenant.balance_before') !== 'tenant.balance_before' ? __('tenant.balance_before') : 'الرصيد قبل';
    const balanceAfterLabel = __('tenant.balance_after') !== 'tenant.balance_after' ? __('tenant.balance_after') : 'الرصيد بعد';

    const associationName = escapeHtml(receiptData.association_name || '');
    const logoUrl = receiptData.logo_url ? escapeHtml(receiptData.logo_url) : '';
    const branchName = escapeHtml(receiptData.branch_name || '');
    const branchPhone = escapeHtml(receiptData.branch_phone || '');
    const branchAddress = escapeHtml(receiptData.branch_address || '');
    const clientName = escapeHtml(receiptData.client_name || '');
    const clientPhone = escapeHtml(receiptData.client_phone || '');
    const balanceBefore = formatBalance(receiptData.balance_before);
    const balanceAfter = formatBalance(receiptData.balance_after);
    const amountPaid = formatBalance(receiptData.amount_paid != null ? receiptData.amount_paid : receiptData.amount_added);
    const occurredAt = escapeHtml(receiptData.occurred_at_display || '');
    const paymentMethod = escapeHtml(receiptData.payment_method_label || receiptData.payment_method || '');
    const paymentRef = escapeHtml(receiptData.payment_reference || '—');
    const cashierName = escapeHtml(receiptData.cashier_name || '');
    const packageName = escapeHtml(receiptData.package_name || '');

    const amountPaidLabel = __('tenant.amount_paid') !== 'tenant.amount_paid' ? __('tenant.amount_paid') : 'المبلغ المدفوع';
    const packageRow = isPackage && packageName
        ? `<tr><td class="receipt-label">${__('tenant.package') !== 'tenant.package' ? __('tenant.package') : 'الباقة'}</td><td class="receipt-value">${packageName}</td></tr>
           <tr><td class="receipt-label">${amountPaidLabel}</td><td class="receipt-value receipt-value-num">${amountPaid}</td></tr>`
        : `<tr><td class="receipt-label">${amountPaidLabel}</td><td class="receipt-value receipt-value-num">${amountPaid}</td></tr>`;

    const html = `<!DOCTYPE html>
<html dir="${htmlDir}" lang="${htmlLang}">
<head>
<meta charset="utf-8"/>
<title>${escapeHtml(title)}</title>
<style>
  * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
  body { font-family: 'Tahoma', 'Arial', sans-serif !important; color: #000 !important; font-size: 11px; font-weight: 600; line-height: 1.3; margin: 0; padding: 8px; direction: ${bodyDirection}; text-align: ${bodyTextAlign}; }
  .receipt-root { max-width: 100%; }
  .receipt-association-name { text-align: center; font-weight: 600; margin-bottom: 6px; }
  .receipt-logo { display: block; margin: 0 auto 6px; max-width: 60mm; max-height: 25mm; object-fit: contain; filter: grayscale(100%) contrast(1.05); }
  .receipt-title { text-align: center; font-weight: 700; font-size: 14px; margin-bottom: 8px; }
  .receipt-section { margin-bottom: 6px; }
  table.receipt-table { width: 100%; border-collapse: collapse; font-size: 11px; font-weight: 600; border: 1px solid #999; }
  table.receipt-table td { padding: 4px 8px; vertical-align: middle; border: 1px solid #999; }
  .receipt-label { font-weight: 600; width: 40%; }
  .receipt-value { }
  .receipt-value-num { text-align: ${isRtl ? 'right' : 'left'}; direction: ltr; }
</style>
</head>
<body class="receipt-root">
${associationName ? `<div class="receipt-section receipt-association-name">${associationName}</div>` : ''}
${logoUrl ? `<div class="receipt-section"><img src="${logoUrl}" alt="logo" class="receipt-logo"/></div>` : ''}
<h2 class="receipt-title">${escapeHtml(title)}</h2>
<div class="receipt-section">
  <table class="receipt-table">
    <tr><td class="receipt-label">${__('tenant.branch') !== 'tenant.branch' ? __('tenant.branch') : 'الفرع'}</td><td class="receipt-value">${branchName}</td></tr>
    <tr><td class="receipt-label">${__('tenant.phone') !== 'tenant.phone' ? __('tenant.phone') : 'هاتف'}</td><td class="receipt-value">${branchPhone}</td></tr>
    <tr><td class="receipt-label">${__('tenant.address') !== 'tenant.address' ? __('tenant.address') : 'العنوان'}</td><td class="receipt-value">${branchAddress}</td></tr>
    <tr><td class="receipt-label">${__('tenant.client_name_invoice') !== 'tenant.client_name_invoice' ? __('tenant.client_name_invoice') : 'أسم العميل'}</td><td class="receipt-value">${clientName} - ${clientPhone}</td></tr>
    ${packageRow}
    <tr><td class="receipt-label">${balanceBeforeLabel}</td><td class="receipt-value receipt-value-num">${balanceBefore}</td></tr>
    <tr><td class="receipt-label">${balanceAfterLabel}</td><td class="receipt-value receipt-value-num">${balanceAfter}</td></tr>
    <tr><td class="receipt-label">${__('tenant.date_of_creation') !== 'tenant.date_of_creation' ? __('tenant.date_of_creation') : 'التاريخ'}</td><td class="receipt-value">${occurredAt}</td></tr>
    <tr><td class="receipt-label">${__('tenant.payment_method') !== 'tenant.payment_method' ? __('tenant.payment_method') : 'طريقة الدفع'}</td><td class="receipt-value">${paymentMethod}</td></tr>
    <tr><td class="receipt-label">${__('tenant.reference_number') !== 'tenant.reference_number' ? __('tenant.reference_number') : 'الرقم المرجعي'}</td><td class="receipt-value">${paymentRef}</td></tr>
    <tr><td class="receipt-label">${__('tenant.cashier') !== 'tenant.cashier' ? __('tenant.cashier') : 'الكاشير'}</td><td class="receipt-value">${cashierName}</td></tr>
  </table>
</div>
</body>
</html>`;

    const iframe = document.createElement('iframe');
    Object.assign(iframe.style, {
        position: 'absolute',
        width: '0',
        height: '0',
        border: 'none',
        visibility: 'hidden',
    });
    iframe.setAttribute('data-ignore-adminlte', 'true');
    document.body.appendChild(iframe);

    const doc = iframe.contentDocument || iframe.contentWindow?.document;
    if (!doc) {
        document.body.removeChild(iframe);
        return;
    }
    doc.open();
    doc.write(html);
    doc.close();

    let printScheduled = false;
    const scheduleSubscriptionReceiptPrint = () => {
        if (printScheduled) {
            return;
        }
        printScheduled = true;
        window.setTimeout(() => {
            try {
                if (iframe.contentWindow) {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                } else {
                    console.error('Unable to access iframe content window for subscription receipt print.');
                }
            } catch (error) {
                console.error('Error occurred while trying to print subscription receipt:', error);
            } finally {
                if (iframe.parentNode) {
                    document.body.removeChild(iframe);
                }
            }
        }, delay);
    };

    const logoImg = iframe.contentWindow?.document?.querySelector('img.receipt-logo');
    if (logoImg && logoImg.src) {
        if (logoImg.complete) {
            scheduleSubscriptionReceiptPrint();
        } else {
            logoImg.addEventListener('load', scheduleSubscriptionReceiptPrint, { once: true });
            logoImg.addEventListener('error', scheduleSubscriptionReceiptPrint, { once: true });
            window.setTimeout(scheduleSubscriptionReceiptPrint, 10000);
        }
    } else {
        scheduleSubscriptionReceiptPrint();
    }
};

/**
 * Load invoice in a hidden iframe and open the print dialog. Resolves after print is triggered
 * (or on load/print failure) so callers can await before showing follow-up UI (e.g. WhatsApp).
 *
 * @param {number|string} invoiceId
 * @param {number} delay ms after iframe load before calling print()
 * @returns {Promise<void>}
 */
export const printInvoice = (invoiceId, delay = 500) => {
    return new Promise((resolve) => {
        let settled = false;
        const iframe = document.createElement('iframe');

        const cleanup = () => {
            if (iframe.parentNode) {
                try {
                    document.body.removeChild(iframe);
                } catch {
                    // ignore
                }
            }
        };

        const finish = () => {
            if (settled) {
                return;
            }
            settled = true;
            window.clearTimeout(safetyTimer);
            cleanup();
            resolve();
        };

        const safetyTimer = window.setTimeout(() => {
            // eslint-disable-next-line no-console
            console.error('Invoice print iframe load timeout.');
            finish();
        }, 30000);

        const waitForImages = (doc, timeoutMs = 10000) => {
            return new Promise((res) => {
                try {
                    const imgs = Array.from(doc?.images || []);
                    if (imgs.length === 0) {
                        res();
                        return;
                    }

                    let done = false;
                    let remaining = 0;
                    const finishWait = () => {
                        if (done) return;
                        done = true;
                        res();
                    };

                    const timer = window.setTimeout(finishWait, timeoutMs);

                    const onOneDone = () => {
                        remaining -= 1;
                        if (remaining <= 0) {
                            window.clearTimeout(timer);
                            finishWait();
                        }
                    };

                    imgs.forEach((img) => {
                        // Some browsers report complete=true even if naturalWidth=0 on error.
                        if (img.complete && img.naturalWidth > 0) {
                            return;
                        }
                        remaining += 1;
                        img.addEventListener('load', onOneDone, { once: true });
                        img.addEventListener('error', onOneDone, { once: true });
                    });

                    if (remaining === 0) {
                        window.clearTimeout(timer);
                        finishWait();
                    }
                } catch {
                    res();
                }
            });
        };

        Object.assign(iframe.style, {
            position: 'absolute',
            width: '0',
            height: '0',
            border: 'none',
            visibility: 'hidden',
        });

        iframe.setAttribute('data-ignore-adminlte', 'true');

        iframe.onload = () => {
            window.clearTimeout(safetyTimer);
            if (settled) {
                return;
            }
            (async () => {
                if (settled) return;

                const win = iframe.contentWindow;
                const doc = iframe.contentDocument || win?.document;
                if (!win || !doc) {
                    // eslint-disable-next-line no-console
                    console.error("Unable to access iframe content window for printing.");
                    finish();
                    return;
                }

                // Wait for images (logo, etc.) to avoid missing assets in print.
                await waitForImages(doc, 10000);

                // Give layout a tiny moment to settle (fonts/CSS).
                await new Promise((r) => window.setTimeout(r, delay));

                if (settled) return;

                // Wait until print dialog is closed to avoid follow-up UI stealing focus
                // (WhatsApp prompt / redirect), which can cancel the print preview.
                let afterPrintFired = false;
                const afterPrintTimeoutMs = 60000;
                const afterPrintTimer = window.setTimeout(() => {
                    if (afterPrintFired) return;
                    // eslint-disable-next-line no-console
                    console.warn('Invoice print afterprint timeout; continuing.');
                    finish();
                }, afterPrintTimeoutMs);

                const onAfterPrint = () => {
                    if (afterPrintFired) return;
                    afterPrintFired = true;
                    window.clearTimeout(afterPrintTimer);
                    finish();
                };

                try {
                    win.addEventListener('afterprint', onAfterPrint, { once: true });
                } catch {
                    // ignore
                }

                try {
                    win.focus();
                    win.print();
                } catch (error) {
                    // eslint-disable-next-line no-console
                    console.error("Error occurred while trying to print:", error);
                    window.clearTimeout(afterPrintTimer);
                    finish();
                }
            })();
        };

        iframe.onerror = () => {
            window.clearTimeout(safetyTimer);
            // eslint-disable-next-line no-console
            console.error("Failed to load iframe content.");
            finish();
        };

        document.body.appendChild(iframe);
        iframe.src = `/invoices/${invoiceId}`;
    });
};

/**
 * Best-effort: open a window synchronously (must be called inside a user gesture).
 * Returns the window handle or null if blocked.
 *
 * @returns {Window|null}
 */
export const openInvoicePrintWindow = () => {
    try {
        const win = window.open('', '_blank');
        if (!win) return null;

        // Give the user something immediately (helps some browsers keep the popup alive).
        // Keep it same-origin and minimal; the actual invoice will be loaded after save succeeds.
        win.document.open();
        win.document.write('<!doctype html><html><head><meta charset="utf-8"><title>Print</title></head><body></body></html>');
        win.document.close();

        return win;
    } catch (e) {
        // eslint-disable-next-line no-console
        console.error('Unable to open invoice print window:', e);
        return null;
    }
};

/**
 * Navigate an existing window to the invoice page and trigger print from that window.
 * This is more reliable than iframe printing when called from a window opened by user gesture.
 *
 * @param {Window|null} win
 * @param {number|string} invoiceId
 * @param {{delay?: number, timeoutMs?: number}} opts
 * @returns {Promise<boolean>} true if we attempted printing, false if no usable window
 */
export const printInvoiceViaWindow = (win, invoiceId, opts = {}) => {
    const delay = Number.isFinite(opts.delay) ? opts.delay : 300;
    const timeoutMs = Number.isFinite(opts.timeoutMs) ? opts.timeoutMs : 30000;

    if (!win) return Promise.resolve(false);

    return new Promise((resolve) => {
        let settled = false;
        const finish = (ok) => {
            if (settled) return;
            settled = true;
            window.clearTimeout(safetyTimer);
            resolve(Boolean(ok));
        };

        const safetyTimer = window.setTimeout(() => {
            // eslint-disable-next-line no-console
            console.error('Invoice print window load timeout.');
            finish(false);
        }, timeoutMs);

        try {
            // Ensure the window is still open.
            if (win.closed) {
                finish(false);
                return;
            }

            // Printing logic injected into the print window (same-origin).
            // It retries a few times because some browsers need a tick after load.
            win.__ELS_PRINT_INVOICE__ = () => {
                try {
                    win.focus();
                    win.print();
                } catch (e) {
                    // eslint-disable-next-line no-console
                    console.error('Invoice print window error:', e);
                } finally {
                    finish(true);
                }
            };

            // Attach a one-time load handler, then navigate.
            const onLoad = () => {
                try {
                    // Delay a bit to allow assets/layout to settle.
                    win.setTimeout(() => {
                        if (typeof win.__ELS_PRINT_INVOICE__ === 'function') {
                            win.__ELS_PRINT_INVOICE__();
                        } else {
                            finish(false);
                        }
                    }, delay);
                } catch {
                    finish(false);
                }
            };

            try {
                win.addEventListener('load', onLoad, { once: true });
            } catch {
                // Some browsers may not allow addEventListener on window; fallback to onload.
                win.onload = onLoad;
            }

            win.location.href = `/invoices/${invoiceId}`;
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error('Unable to print invoice via window:', e);
            finish(false);
        }
    });
};
