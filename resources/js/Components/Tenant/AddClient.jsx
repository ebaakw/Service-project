import React from 'react';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import ClientFormFields from '@/Components/Tenant/ClientFormFields.jsx';
import { useForm } from '@inertiajs/react';

/**
 * مكوّن موحد لإضافة عميل داخل Dialog (يُستخدم من شاشة الفاتورة وطلبات السائق).
 * يستخدم ClientFormFields ويرسل إلى api.clients.
 */
const AddClient = ({ closeClient, cities, postpaidWalletsEnabled = true }) => {
    const { data, setData, post, processing, errors, reset, transform } = useForm({
        name: '',
        phone: '',
        mobile: '',
        address: '',
        email: '',
        note: '',
        billing_type: 'prepaid',
        payday_at: '',
        area_id: null,
        city_id: null,
        status: true,
    });

    React.useEffect(() => {
        transform((payload) => ({
            ...payload,
            city_id: payload.city_id != null && payload.city_id !== '' ? Number(payload.city_id) : null,
            area_id: payload.area_id != null && payload.area_id !== '' ? Number(payload.area_id) : null,
        }));
    }, []);

    const handleSubmit = (e) => {
        if (e && e.preventDefault) e.preventDefault();
        if (e && e.stopPropagation) e.stopPropagation();
        post(route('api.clients'), {
            onSuccess() {
                const createdName = data?.name ?? '';
                const createdPhone = data?.phone ?? '';
                try {
                    window.dispatchEvent(new CustomEvent('tenant:client-created', {
                        detail: { name: createdName, phone: createdPhone },
                    }));
                } catch (_) {
                    // ignore (older browsers / SSR)
                }
                closeClient();
                reset();
            },
        });
    };

    return (
        <div
            role="form"
            onKeyDown={(e) => {
                if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                    handleSubmit(e);
                }
            }}
        >
            <ClientFormFields
                data={data}
                setData={setData}
                errors={errors}
                cities={cities}
                idPrefix="invoice-add-client"
                postpaidWalletsEnabled={postpaidWalletsEnabled}
                footer={
                    <>
                        <button
                            type="button"
                            className="btn btn-primary btn-sm px-3 py-2 mt-3"
                            disabled={processing}
                            onClick={handleSubmit}
                        >
                            {__('tenant.submit')}
                        </button>
                        <button
                            type="button"
                            onClick={closeClient}
                            className="btn btn-danger btn-sm px-3 py-2 mx-2 mt-3"
                            disabled={processing}
                        >
                            {__('tenant.cancel')}
                        </button>
                    </>
                }
            />
        </div>
    );
};

export default AddClient;
