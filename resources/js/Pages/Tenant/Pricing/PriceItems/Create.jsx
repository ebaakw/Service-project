import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { fieldError, focusField, focusFirstError, getRequiredFieldErrors, invalidClass } from './formValidation';
import '@/Components/Core/css/switch.css';

const PRICE_TYPES = ['fixed', 'starting_from', 'hourly', 'custom_quote'];
const DISCOUNT_TYPES = ['percentage', 'fixed_amount'];

function RequiredMark() {
    return <span className="text-danger"> *</span>;
}

export default function TenantPriceItemsCreate({ priceLists, categories, defaultPriceListId }) {
    const [clientErrors, setClientErrors] = useState({});
    const initialPriceListId = defaultPriceListId ? Number(defaultPriceListId) : priceLists?.[0]?.id ?? '';
    const initialPriceList = priceLists?.find((priceList) => Number(priceList.id) === Number(initialPriceListId));
    const initialCategories = initialPriceList?.service_categories?.length ? initialPriceList.service_categories : categories;

    const { data, setData, post, processing, errors } = useForm({
        company_price_list_id: initialPriceListId,
        service_category_id: initialCategories[0]?.id ?? '',
        name_ar: '',
        name_en: '',
        description_ar: '',
        description_en: '',
        price_type: 'fixed',
        price: '',
        minimum_price: '',
        discount_type: '',
        discount_value: '',
        warranty_days: '',
        warranty_description_ar: '',
        warranty_description_en: '',
        sort_order: 0,
        is_active: true,
    });

    const setField = (field, value) => {
        setData(field, value);

        if (clientErrors[field]) {
            setClientErrors((current) => {
                const next = { ...current };
                delete next[field];

                return next;
            });
        }
    };

    const submit = (e) => {
        e.preventDefault();

        const nextClientErrors = getRequiredFieldErrors(data, __('tenant.field_required'));
        setClientErrors(nextClientErrors);

        const firstInvalidField = Object.keys(nextClientErrors)[0];
        if (firstInvalidField) {
            focusField(firstInvalidField);
            return;
        }

        post(route('price-items.store'), {
            onError: (serverErrors) => focusFirstError(serverErrors),
        });
    };

    const availableCategories = useMemo(() => {
        const selectedList = priceLists?.find((priceList) => Number(priceList.id) === Number(data.company_price_list_id));

        return selectedList?.service_categories?.length ? selectedList.service_categories : [];
    }, [data.company_price_list_id, priceLists]);

    const setSelectedPriceList = (value) => {
        const nextPriceListId = Number(value);
        const selectedList = priceLists?.find((priceList) => Number(priceList.id) === nextPriceListId);
        const nextCategories = selectedList?.service_categories ?? [];

        setData('company_price_list_id', nextPriceListId);
        setData('service_category_id', nextCategories[0]?.id ?? '');
        setClientErrors((current) => {
            const next = { ...current };
            delete next.company_price_list_id;
            delete next.service_category_id;

            return next;
        });
    };

    if (!priceLists?.length) {
        return (
            <CompanyDashboardLayout breadcrumbKey="dashboard.nav.company_price_items">
                <Head title={__('tenant.price_items')} />
                <section className="content">
                    <div className="container-fluid">
                        <div className="alert alert-warning">
                            {__('tenant.select_price_list')} —{' '}
                            <Link href={route('price-lists.create')}>{__('tenant.add_price_list')}</Link>
                        </div>
                    </div>
                </section>
            </CompanyDashboardLayout>
        );
    }

    return (
        <CompanyDashboardLayout breadcrumbKey="dashboard.nav.company_price_items">
            <Head title={__('tenant.add_price_item')} />

            <section className="content">
                <div className="container-fluid">
                    <div className="card card-outline card-primary">
                        <div className="card-header">
                            <h3 className="card-title">{__('tenant.add_price_item')}</h3>
                            <div className="card-tools">
                                <Link href={route('price-items.index')} className="btn btn-tool btn-sm">
                                    ←
                                </Link>
                            </div>
                        </div>
                        <form onSubmit={submit} className="card-body">
                            <div className="form-group">
                                <label>
                                    {__('tenant.select_price_list')}
                                    <RequiredMark />
                                </label>
                                <select
                                    name="company_price_list_id"
                                    className={invalidClass('custom-select', 'company_price_list_id', errors, clientErrors)}
                                    value={data.company_price_list_id}
                                    onChange={(e) => setSelectedPriceList(e.target.value)}
                                >
                                    {priceLists.map((pl) => (
                                        <option key={pl.id} value={pl.id}>
                                            {pl.name}
                                        </option>
                                    ))}
                                </select>
                                {fieldError('company_price_list_id', errors, clientErrors) && (
                                    <span className="text-danger small d-block">
                                        {fieldError('company_price_list_id', errors, clientErrors)}
                                    </span>
                                )}
                            </div>

                            <div className="form-group">
                                <label>
                                    {__('tenant.select_category')}
                                    <RequiredMark />
                                </label>
                                {availableCategories.length === 0 && (
                                    <div className="alert alert-warning py-2">
                                        {__('tenant.price_list_has_no_categories')}
                                    </div>
                                )}
                                <select
                                    name="service_category_id"
                                    className={invalidClass('custom-select', 'service_category_id', errors, clientErrors)}
                                    value={data.service_category_id}
                                    onChange={(e) => {
                                        const v = Number(e.target.value);
                                        setField('service_category_id', v);
                                    }}
                                >
                                    {availableCategories.map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.name_ar}
                                        </option>
                                    ))}
                                </select>
                                {fieldError('service_category_id', errors, clientErrors) && (
                                    <span className="text-danger small d-block">
                                        {fieldError('service_category_id', errors, clientErrors)}
                                    </span>
                                )}
                            </div>

                            <div className="form-group">
                                <label>
                                    {__('tenant.item_name_ar')}
                                    <RequiredMark />
                                </label>
                                <input
                                    name="name_ar"
                                    className={invalidClass('form-control', 'name_ar', errors, clientErrors)}
                                    value={data.name_ar}
                                    onChange={(e) => setField('name_ar', e.target.value)}
                                />
                                {fieldError('name_ar', errors, clientErrors) && (
                                    <span className="text-danger small d-block">{fieldError('name_ar', errors, clientErrors)}</span>
                                )}
                            </div>
                            <div className="form-group">
                                <label>
                                    {__('tenant.item_name_en')}
                                    <RequiredMark />
                                </label>
                                <input
                                    name="name_en"
                                    className={invalidClass('form-control', 'name_en', errors, clientErrors)}
                                    value={data.name_en}
                                    onChange={(e) => setField('name_en', e.target.value)}
                                />
                                {fieldError('name_en', errors, clientErrors) && (
                                    <span className="text-danger small d-block">{fieldError('name_en', errors, clientErrors)}</span>
                                )}
                            </div>

                            <div className="form-group">
                                <label>{__('tenant.description_ar')}</label>
                                <textarea
                                    name="description_ar"
                                    className="form-control"
                                    rows={2}
                                    value={data.description_ar}
                                    onChange={(e) => setData('description_ar', e.target.value)}
                                />
                            </div>
                            <div className="form-group">
                                <label>{__('tenant.description_en')}</label>
                                <textarea
                                    name="description_en"
                                    className="form-control"
                                    rows={2}
                                    value={data.description_en}
                                    onChange={(e) => setData('description_en', e.target.value)}
                                />
                            </div>

                            <div className="form-group">
                                <label>
                                    {__('tenant.price_type')}
                                    <RequiredMark />
                                </label>
                                <select
                                    name="price_type"
                                    className={invalidClass('custom-select', 'price_type', errors, clientErrors)}
                                    value={data.price_type}
                                    onChange={(e) => setField('price_type', e.target.value)}
                                >
                                    {PRICE_TYPES.map((t) => (
                                        <option key={t} value={t}>
                                            {__(`tenant.price_type_${t}`)}
                                        </option>
                                    ))}
                                </select>
                                {fieldError('price_type', errors, clientErrors) && (
                                    <span className="text-danger small d-block">{fieldError('price_type', errors, clientErrors)}</span>
                                )}
                            </div>

                            <div className="form-row">
                                <div className="form-group col-md-6">
                                    <label>{__('tenant.price')}</label>
                                    <input
                                        name="price"
                                        type="number"
                                        step="0.001"
                                        className={invalidClass('form-control', 'price', errors, clientErrors)}
                                        value={data.price}
                                        onChange={(e) => setData('price', e.target.value)}
                                    />
                                    {errors.price && <span className="text-danger small d-block">{errors.price}</span>}
                                </div>
                                <div className="form-group col-md-6">
                                    <label>{__('tenant.minimum_price')}</label>
                                    <input
                                        name="minimum_price"
                                        type="number"
                                        step="0.001"
                                        className={invalidClass('form-control', 'minimum_price', errors, clientErrors)}
                                        value={data.minimum_price}
                                        onChange={(e) => setData('minimum_price', e.target.value)}
                                    />
                                    {errors.minimum_price && (
                                        <span className="text-danger small d-block">{errors.minimum_price}</span>
                                    )}
                                </div>
                            </div>

                            <div className="form-row">
                                <div className="form-group col-md-6">
                                    <label>{__('tenant.price_item_discount')}</label>
                                    <select
                                        name="discount_type"
                                        className={invalidClass('custom-select', 'discount_type', errors, clientErrors)}
                                        value={data.discount_type}
                                        onChange={(e) => {
                                            setData('discount_type', e.target.value);
                                            if (!e.target.value) {
                                                setData('discount_value', '');
                                            }
                                        }}
                                    >
                                        <option value="">{__('tenant.no_discount')}</option>
                                        {DISCOUNT_TYPES.map((type) => (
                                            <option key={type} value={type}>
                                                {__(`tenant.discount_type_${type}`)}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.discount_type && (
                                        <span className="text-danger small d-block">{errors.discount_type}</span>
                                    )}
                                </div>
                                <div className="form-group col-md-6">
                                    <label>{__('tenant.discount_value')}</label>
                                    <input
                                        name="discount_value"
                                        type="number"
                                        step="0.001"
                                        className={invalidClass('form-control', 'discount_value', errors, clientErrors)}
                                        value={data.discount_value}
                                        disabled={!data.discount_type}
                                        onChange={(e) => setData('discount_value', e.target.value)}
                                    />
                                    {errors.discount_value && (
                                        <span className="text-danger small d-block">{errors.discount_value}</span>
                                    )}
                                </div>
                            </div>

                            <div className="form-row">
                                <div className="form-group col-md-4">
                                    <label>{__('tenant.warranty_days')}</label>
                                    <input
                                        name="warranty_days"
                                        type="number"
                                        className={invalidClass('form-control', 'warranty_days', errors, clientErrors)}
                                        value={data.warranty_days}
                                        onChange={(e) => setData('warranty_days', e.target.value)}
                                    />
                                    {errors.warranty_days && (
                                        <span className="text-danger small d-block">{errors.warranty_days}</span>
                                    )}
                                </div>
                            </div>
                            <div className="form-group">
                                <label>{__('tenant.warranty_desc_ar')}</label>
                                <textarea
                                    name="warranty_description_ar"
                                    className="form-control"
                                    rows={2}
                                    value={data.warranty_description_ar}
                                    onChange={(e) => setData('warranty_description_ar', e.target.value)}
                                />
                            </div>
                            <div className="form-group">
                                <label>{__('tenant.warranty_desc_en')}</label>
                                <textarea
                                    name="warranty_description_en"
                                    className="form-control"
                                    rows={2}
                                    value={data.warranty_description_en}
                                    onChange={(e) => setData('warranty_description_en', e.target.value)}
                                />
                            </div>

                            <div className="form-group">
                                <label>{__('tenant.sort_order')}</label>
                                <input
                                    name="sort_order"
                                    type="number"
                                    className={invalidClass('form-control', 'sort_order', errors, clientErrors)}
                                    value={data.sort_order}
                                    onChange={(e) => setData('sort_order', Number(e.target.value))}
                                />
                                {errors.sort_order && <span className="text-danger small d-block">{errors.sort_order}</span>}
                            </div>

                            <div className="form-group custom-control custom-switch custom-switch-md">
                                <input
                                    id="pi_active"
                                    name="is_active"
                                    type="checkbox"
                                    className="custom-control-input"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                />
                                <label className="custom-control-label pt-1 pl-3" htmlFor="pi_active">
                                    {__('tenant.active')}
                                </label>
                            </div>

                            <button type="submit" className="btn btn-primary" disabled={processing}>
                                {__('tenant.save')}
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </CompanyDashboardLayout>
    );
}
