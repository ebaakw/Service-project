import {useRef, useState} from "react";

export function Dialog(
    {
        trigger,
        title,
        size = '',
        children,
        footer = true,
        action,
        btnSaveText = 'Submit',
        btnCloseText = 'Close'
    }) {
    return (
        <div ref={trigger} className="modal fade" tabIndex={-1} role='dialog'>
            <div className={`modal-dialog modal-dialog-centered modal-${size}`} role='document'>
                <div className="modal-content">

                    {/* Header */}
                    <div className="modal-header">
                        <div className="modal-title">{title}</div>
                        <button type="button" className="close m-0 p-0" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    {/* Body */}
                    <div className="modal-body">
                        {children}
                    </div>

                    {/*Footer*/}
                    {
                        footer &&
                        <div className="modal-footer">
                            <button onClick={action} type="button" className="btn btn-primary">
                                {btnSaveText}
                            </button>
                            <button type="button" className="btn btn-danger" data-dismiss="modal">
                                {btnCloseText}
                            </button>
                        </div>
                    }

                </div>
            </div>
        </div>
    )
}

export function useDialog(onShown) {
    const modal = useRef();
    const modalInstance = useRef();
    const onShownRef = useRef(onShown);
    onShownRef.current = onShown;
    const [isOpen, setIsOpen] = useState(false);

    const open = () => {

        if (!modalInstance.current) {
            modalInstance.current = new window.bootstrap.Modal(modal.current);
        }
        const el = modal.current;
        if (el && typeof onShownRef.current === 'function') {
            const handler = () => {
                el.removeEventListener('shown.bs.modal', handler);
                const cb = onShownRef.current;
                [50, 150, 300, 500].forEach((ms) => setTimeout(() => cb?.(el), ms));
            };
            el.addEventListener('shown.bs.modal', handler);
        }
        modalInstance.current.show();
        setIsOpen(true);
    }

    const close = () => {
        if (modalInstance.current) {
            modalInstance.current.hide()
            setIsOpen(false);
        } else {
            console.warn('not found instance of modal')
        }
    }

    return [open, close, modal, isOpen];
}
