import axios from 'axios';
import Swal from 'sweetalert2';

/**
 * SweetAlert success + optional GET invoices.whatsapp then open wa.me (same pattern as subscription payment success).
 *
 * @param {object} params
 * @param {number|string} params.invoiceId
 * @param {string} [params.title] Swal title; falls back to invoice_status_updated
 * @param {function} params.__ translate function from useTranslate
 * @param {boolean} [params.enabled] when false, no dialog (tenant setting whatsapp_prompt_after_success)
 */
export async function promptSendInvoiceWhatsApp({ invoiceId, title, __, enabled = true }) {
    if (enabled === false) {
        return;
    }

    const id = invoiceId;
    if (id == null || id === '') {
        return;
    }

    const result = await Swal.fire({
        icon: 'success',
        title: title ?? __('tenant.invoice_status_updated'),
        showCancelButton: true,
        confirmButtonText: __('tenant.send_whatsapp'),
        cancelButtonText: __('tenant.cancel'),
    });

    if (!result?.isConfirmed) {
        return;
    }

    try {
        const { data } = await axios.get(route('invoices.whatsapp', id));
        const waUrl = data?.wa_url;

        if (!waUrl) {
            await Swal.fire({
                icon: 'error',
                title: __('tenant.error_loading_data'),
            });
            return;
        }

        window.open(waUrl, '_blank', 'noopener,noreferrer');
    } catch (error) {
        const msg =
            error?.response?.data?.message ||
            __('tenant.error_loading_data');
        await Swal.fire({
            icon: 'error',
            title: msg,
        });
    }
}
