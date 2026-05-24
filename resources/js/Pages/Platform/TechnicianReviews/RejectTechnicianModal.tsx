import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { FormEvent } from 'react';
import { useEffect, useState } from 'react';

type RejectTechnicianModalProps = {
    show: boolean;
    technicianName?: string | null;
    processing?: boolean;
    onClose: () => void;
    onConfirm: (reason: string) => void;
};

export default function RejectTechnicianModal({
    show,
    technicianName,
    processing = false,
    onClose,
    onConfirm,
}: RejectTechnicianModalProps) {
    const [reason, setReason] = useState('');
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!show) {
            setReason('');
            setError(null);
        }
    }, [show]);

    if (!show) {
        return null;
    }

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const trimmedReason = reason.trim();
        if (trimmedReason.length < 3) {
            setError(__('dashboard.technician_reviews.rejection_reason_required'));
            return;
        }

        setError(null);
        onConfirm(trimmedReason);
    };

    return (
        <>
            <div className="modal fade show d-block" tabIndex={-1} role="dialog" aria-modal="true">
                <div className="modal-dialog modal-dialog-centered" role="document">
                    <form className="modal-content" onSubmit={submit}>
                        <div className="modal-header">
                            <h5 className="modal-title">{__('dashboard.technician_reviews.reject_title')}</h5>
                            <button type="button" className="close m-0 p-0" aria-label="Close" onClick={onClose} disabled={processing}>
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div className="modal-body">
                            {technicianName ? (
                                <div className="alert alert-warning py-2">
                                    {__('dashboard.technician_reviews.reject_confirm')}: <strong>{technicianName}</strong>
                                </div>
                            ) : null}
                            <div className="form-group mb-0">
                                <label htmlFor="technician-rejection-reason">{__('dashboard.technician_reviews.rejection_reason')}</label>
                                <textarea
                                    id="technician-rejection-reason"
                                    className={`form-control ${error ? 'is-invalid' : ''}`}
                                    rows={4}
                                    value={reason}
                                    onChange={(event) => setReason(event.target.value)}
                                    placeholder={__('dashboard.technician_reviews.rejection_reason_placeholder')}
                                    disabled={processing}
                                    autoFocus
                                />
                                {error ? <div className="invalid-feedback d-block">{error}</div> : null}
                            </div>
                        </div>
                        <div className="modal-footer">
                            <button type="button" className="btn btn-outline-secondary" onClick={onClose} disabled={processing}>
                                {__('tenant.cancel')}
                            </button>
                            <button type="submit" className="btn btn-danger" disabled={processing}>
                                {processing ? __('tenant.loading') : __('dashboard.technician_reviews.reject')}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div className="modal-backdrop fade show" />
        </>
    );
}
