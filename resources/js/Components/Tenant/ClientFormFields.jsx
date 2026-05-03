import React, { useState } from 'react';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import '@/Components/Core/css/switch.css';
import Select from 'react-select';
import { usePage } from '@inertiajs/react';
import { ensureKuwaitLocationMaps, getKuwaitAreaLabel, getKuwaitProvinceLabel } from '@/Utility/kuwaitLocations.js';

/**
 * نموذج حقول إنشاء/تعديل عميل مشترك.
 * يُستخدم في: صفحة إنشاء عميل (Clients/Create) ومكوّن إضافة عميل في البوب اب (Components/Tenant/AddClient).
 * العرض يختلف حسب الاستخدام (صفحة كاملة أو داخل Dialog).
 *
 * @param {Object} props
 * @param {Object} props.data - بيانات النموذج من useForm
 * @param {Function} props.setData - دالة تحديث الحقول (من useForm أو onHandleChange)
 * @param {Object} props.errors - أخطاء التحقق
 * @param {Array} props.cities - قائمة المدن (يدعم name أو ar_name)
 * @param {string} [props.idPrefix=''] - بادئة لـ id العناصر لتجنب التكرار عند استخدام النموذج في صفحة وpopup
 * @param {React.ReactNode} [props.footer] - محتوى يظهر تحت الحقول (مثل أزرار الحفظ/إلغاء)
 * @param {boolean} [props.postpaidWalletsEnabled=true] - عند false يُخفى قسم نوع المحفظة بالكامل (الافتراضي مسبق الدفع ولا خيارات أخرى)
 */
const ClientFormFields = ({ data, setData, errors = {}, cities = [], idPrefix = '', footer, postpaidWalletsEnabled = true }) => {
    const citiesList = Array.isArray(cities) ? cities : [];
    const matchId = (a, b) => a != null && b != null && (a === b || a == b || String(a) === String(b));
    const locale = usePage()?.props?.locale || 'ar';

    const [areas, setAreas] = useState([]);
    const [selectedCity, setSelectedCity] = useState(null);
    const [selectedArea, setSelectedArea] = useState(null);

    React.useEffect(() => {
        const city = citiesList.find((c) => matchId(c?.id, data.city_id)) || null;
        setSelectedCity(city);
    }, [data.city_id, citiesList]);

    React.useEffect(() => {
        const area = areas.find((a) => matchId(a?.id, data.area_id)) || null;
        setSelectedArea(area);
    }, [data.area_id, areas]);

    React.useEffect(() => {
        // Only needed when locale is EN and DB values are missing/Arabic.
        ensureKuwaitLocationMaps().then(() => {});
    }, []);

    const onHandleChange = (event) => {
        setData(event.target.name, event.target.type === 'checkbox' ? event.target.checked : event.target.value);
    };

    const mapInput = (event, name) => ({ target: { name, value: event?.id ?? event?.value } });

    const handleCityChange = (option) => {
        const cityId = option?.id ?? option?.value ?? option?.target?.value;
        const normalizedCityId = cityId != null && cityId !== '' ? (Number(cityId) || cityId) : null;
        setSelectedCity(option && typeof option === 'object' && !option.target ? option : null);
        setSelectedArea(null);
        setData((prev) => ({ ...prev, city_id: normalizedCityId ?? null, area_id: null }));
        if (!normalizedCityId) {
            setAreas([]);
            return;
        }
        setAreas([]);
        let areasPath = '/api/areas';
        try {
            if (typeof route === 'function') {
                const r = route('api.areas');
                if (r && typeof r === 'string') areasPath = r;
            }
        } catch (_) {}
        const url = (areasPath.includes('?') ? areasPath + '&' : areasPath + '?') + 'city=' + encodeURIComponent(normalizedCityId);
        fetch(url, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then((res) => {
                const contentType = res.headers.get('content-type') || '';
                if (!res.ok) {
                    throw new Error('Areas fetch failed: ' + res.status);
                }
                if (!contentType.includes('application/json')) {
                    throw new Error('Response is not JSON');
                }
                return res.json();
            })
            .then((json) => {
                const list = Array.isArray(json) ? json : (json?.data != null ? (Array.isArray(json.data) ? json.data : []) : []);
                setAreas(list);
            })
            .catch((err) => {
                console.error('Error fetching areas:', err);
                setAreas([]);
            });
    };

    const handleAreaChange = (option) => {
        const areaId = option != null ? (option.id ?? option.value ?? null) : null;
        const normalizedAreaId = areaId != null && areaId !== '' ? (Number(areaId) || areaId) : null;
        setSelectedArea(option ?? null);
        setData((prev) => ({ ...prev, area_id: normalizedAreaId ?? null }));
    };

    const cityOptionLabel = (option) => getKuwaitProvinceLabel(option, locale);
    const areaOptionLabel = (option) => getKuwaitAreaLabel(option, locale);

    const pid = (s) => (idPrefix ? `${idPrefix}-${s}` : s);

    return (
        <div className="row">
            <div className="col-sm-6">
                <label>{__('tenant.name')} <span className="text-danger">*</span></label>
                <input
                    className="form-control"
                    name="name"
                    type="text"
                    value={data.name ?? ''}
                    placeholder={__('tenant.name')}
                    onChange={onHandleChange}
                />
                <div className="text-danger mb-3">{errors.name}</div>
            </div>

            <div className="col-sm-6">
                <label>{__('tenant.phone')} <span className="text-danger">*</span></label>
                <input
                    className="form-control"
                    name="phone"
                    type="tel"
                    value={data.phone ?? ''}
                    placeholder={__('tenant.phone')}
                    onChange={onHandleChange}
                />
                <div className="text-danger mb-3">{errors.phone}</div>
            </div>

            <div className="col-sm-6">
                <label>{__('tenant.mobile')}</label>
                <input
                    className="form-control"
                    name="mobile"
                    type="text"
                    value={data.mobile ?? ''}
                    placeholder={__('tenant.mobile')}
                    onChange={onHandleChange}
                />
                <div className="text-danger mb-3">{errors.mobile}</div>
            </div>

            <div className="col-sm-6">
                <label>{__('tenant.address')}</label>
                <input
                    className="form-control"
                    name="address"
                    type="text"
                    value={data.address ?? ''}
                    placeholder={__('tenant.address')}
                    onChange={onHandleChange}
                />
                <div className="text-danger mb-3">{errors.address}</div>
            </div>

            <div className="col-md-6">
                <label>{__('tenant.select_city')}</label>
                <Select
                    noOptionsMessage={() => __('tenant.no_options_available')}
                    getOptionLabel={cityOptionLabel}
                    getOptionValue={(option) => option?.id}
                    options={citiesList}
                    value={selectedCity}
                    onChange={handleCityChange}
                    placeholder={__('tenant.select_city')}
                />
                <div className="text-danger mb-3">{errors.city_id}</div>
            </div>

            <div className="col-md-6">
                <label>{__('tenant.select_area')}</label>
                <Select
                    noOptionsMessage={() => __('tenant.no_options_available')}
                    getOptionLabel={areaOptionLabel}
                    getOptionValue={(option) => option?.id}
                    options={areas}
                    value={selectedArea}
                    onChange={handleAreaChange}
                    placeholder={__('tenant.select_area')}
                />
                <div className="text-danger mb-3">{errors.area_id}</div>
            </div>

            <div className="col-md-6">
                <label>{__('tenant.email')}</label>
                <input
                    className="form-control"
                    name="email"
                    type="email"
                    value={data.email ?? ''}
                    placeholder={__('tenant.email')}
                    onChange={onHandleChange}
                />
                <div className="text-danger mb-3">{errors.email}</div>
            </div>

            <div className="col-sm-6">
                <label>{__('tenant.notes')}</label>
                <textarea
                    rows={1}
                    className="form-control"
                    name="note"
                    value={data.note ?? ''}
                    placeholder={__('tenant.notes')}
                    onChange={onHandleChange}
                />
                <div className="text-danger mb-3">{errors.note}</div>
            </div>

            {postpaidWalletsEnabled && (
                <div className="col-12">
                    <span className="d-block mb-2">{__('tenant.wallet_type')}</span>
                    <div className="d-flex flex-wrap align-items-center" style={{ gap: '1.5rem' }}>
                        <label
                            className="form-check form-check-inline mb-0 pe-3 d-flex align-items-center gap-2"
                            style={{ cursor: 'pointer' }}
                        >
                            <input
                                className="form-check-input"
                                type="radio"
                                name="billing_type"
                                id={pid('billing_prepaid')}
                                checked={data.billing_type === 'prepaid'}
                                onChange={onHandleChange}
                                value="prepaid"
                            />
                            <span className="form-check-label">
                                {__('tenant.prepaid_credit')} – {__('tenant.(package_system)')}
                            </span>
                        </label>
                        <label
                            className="form-check form-check-inline mb-0 ps-3 d-flex align-items-center gap-2"
                            style={{ cursor: 'pointer' }}
                        >
                            <input
                                className="form-check-input"
                                type="radio"
                                name="billing_type"
                                id={pid('billing_credit')}
                                checked={data.billing_type === 'credit'}
                                onChange={onHandleChange}
                                value="credit"
                            />
                            <span className="form-check-label">
                                {__('tenant.credit_balance')}
                            </span>
                        </label>
                    </div>
                </div>
            )}

            {postpaidWalletsEnabled && data.billing_type === 'credit' && (
                <div className="col-md-6 mt-2">
                    <label>{__('tenant.client_payday_at')}</label>
                    <input
                        type="datetime-local"
                        className="form-control"
                        name="payday_at"
                        value={data.payday_at ?? ''}
                        onChange={onHandleChange}
                    />
                    <small className="text-muted d-block">{__('tenant.client_payday_at_hint')}</small>
                    {errors.payday_at && <div className="text-danger">{errors.payday_at}</div>}
                </div>
            )}

            <div className="col-md-6">
                <div className="custom-switch custom-switch-md my-4 mx-3">
                    <input
                        type="checkbox"
                        className="custom-control-input"
                        id={pid('status')}
                        name="status"
                        value={data.status}
                        checked={!!data.status}
                        onChange={onHandleChange}
                    />
                    <label className="custom-control-label pt-1 pl-3" htmlFor={pid('status')}>
                        {__('tenant.active')}
                    </label>
                </div>
            </div>

            {footer != null && <div className="col-md-12">{footer}</div>}
        </div>
    );
};

export default ClientFormFields;
