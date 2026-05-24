import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, useForm } from '@inertiajs/react';
import '@/Components/Core/css/switch.css';

const DISCOUNT_TYPES = ['percentage', 'fixed_amount'];

function toLocalInput(dt) {
    if (!dt) {
        return '';
    }
    const d = new Date(dt);
    if (Number.isNaN(d.getTime())) {
        return '';
    }
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

export default function TenantPriceListsEdit({ priceList, categories, selectedCategoryIds }) {
    const { data, setData, put, processing, errors } = useForm({
        name_ar: priceList.name_ar ?? priceList.name ?? '',
        name_en: priceList.name_en ?? priceList.name ?? '',
        description_ar: priceList.description_ar ?? priceList.description ?? '',
        description_en: priceList.description_en ?? priceList.description ?? '',
        is_default: priceList.is_default,
        starts_at: toLocalInput(priceList.starts_at),
        ends_at: toLocalInput(priceList.ends_at),
        discount_type:
            typeof priceList.discount_type === 'object' ? priceList.discount_type?.value ?? '' : priceList.discount_type ?? '',
        discount_value: priceList.discount_value ?? '',
        is_active: priceList.is_active,
        category_ids: selectedCategoryIds ?? [],
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('price-lists.update', priceList.id));
    };

    return (
        <CompanyDashboardLayout breadcrumbKey="dashboard.nav.company_price_lists">
            <Head title={__('tenant.edit_price_list')} />

            <section className="content">
                <div className="container-fluid">
                    <div className="card card-outline card-primary">
                        <div className="card-header">
                            <h3 className="card-title">{__('tenant.edit_price_list')}</h3>
                            <div className="card-tools">
                                <Link href={route('price-lists.index')} className="btn btn-tool btn-sm">
                                    ←
                                </Link>
                            </div>
                        </div>
                        <form onSubmit={submit} className="card-body">
                            <div className="form-row">
                                <div className="form-group col-md-6">
                                    <label>{__('tenant.list_name_ar')}</label>
                                    <input
                                        dir="rtl"
                                        className="form-control"
                                        value={data.name_ar}
                                        onChange={(e) => setData('name_ar', e.target.value)}
                                    />
                                    {errors.name_ar && (
                                        <span className="text-danger small d-block">{errors.name_ar}</span>
                                    )}
                                </div>
                                <div className="form-group col-md-6">
                                    <label>{__('tenant.list_name_en')}</label>
                                    <input
                                        dir="ltr"
                                        className="form-control"
                                        value={data.name_en}
                                        onChange={(e) => setData('name_en', e.target.value)}
                                    />
                                    {errors.name_en && (
                                        <span className="text-danger small d-block">{errors.name_en}</span>
                                    )}
                                </div>
                            </div>
                            <div className="form-row">
                                <div className="form-group col-md-6">
                                    <label>{__('tenant.description_ar')}</label>
                                    <textarea
                                        dir="rtl"
                                        className="form-control"
                                        rows={3}
                                        value={data.description_ar}
                                        onChange={(e) => setData('description_ar', e.target.value)}
                                    />
                                    {errors.description_ar && (
                                        <span className="text-danger small d-block">{errors.description_ar}</span>
                                    )}
                                </div>
                                <div className="form-group col-md-6">
                                    <label>{__('tenant.description_en')}</label>
                                    <textarea
                                        dir="ltr"
                                        className="form-control"
                                        rows={3}
                                        value={data.description_en}
                                        onChange={(e) => setData('description_en', e.target.value)}
                                    />
                                    {errors.description_en && (
                                        <span className="text-danger small d-block">{errors.description_en}</span>
                                    )}
                                </div>
                            </div>
                            <div className="form-row">
                                <div className="form-group col-md-6">
                                    <label>{__('tenant.date_start')}</label>
                                    <input
                                        type="datetime-local"
                                        className="form-control"
                                        value={data.starts_at}
                                        onChange={(e) => setData('starts_at', e.target.value)}
                                    />
                                    {errors.starts_at && (
                                        <span className="text-danger small d-block">{errors.starts_at}</span>
                                    )}
                                </div>
                                <div className="form-group col-md-6">
                                    <label>{__('tenant.date_end')}</label>
                                    <input
                                        type="datetime-local"
                                        className="form-control"
                                        value={data.ends_at}
                                        onChange={(e) => setData('ends_at', e.target.value)}
                                    />
                                    {errors.ends_at && (
                                        <span className="text-danger small d-block">{errors.ends_at}</span>
                                    )}
                                </div>
                            </div>
                            <div className="form-row">
                                <div className="form-group col-md-6">
                                    <label>{__('tenant.price_list_discount')}</label>
                                    <select
                                        className="custom-select"
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
                                        type="number"
                                        step="0.001"
                                        className="form-control"
                                        value={data.discount_value}
                                        disabled={!data.discount_type}
                                        onChange={(e) => setData('discount_value', e.target.value)}
                                    />
                                    {errors.discount_value && (
                                        <span className="text-danger small d-block">{errors.discount_value}</span>
                                    )}
                                </div>
                            </div>
                            <div className="form-group">
                                <label>{__('tenant.price_list_categories')}</label>
                                <select
                                    multiple
                                    className="form-control"
                                    size={Math.min(10, Math.max(4, categories.length))}
                                    value={data.category_ids.map(String)}
                                    onChange={(e) => {
                                        const selected = Array.from(e.target.selectedOptions).map((option) => Number(option.value));
                                        setData('category_ids', selected);
                                    }}
                                >
                                    {categories.map((category) => (
                                        <option key={category.id} value={category.id}>
                                            {category.name_ar}
                                        </option>
                                    ))}
                                </select>
                                <small className="form-text text-muted">{__('tenant.price_list_categories_help')}</small>
                                {errors.category_ids && <span className="text-danger small d-block">{errors.category_ids}</span>}
                            </div>
                            <div className="form-group custom-control custom-switch custom-switch-md">
                                <input
                                    id="is_default"
                                    type="checkbox"
                                    className="custom-control-input"
                                    checked={data.is_default}
                                    onChange={(e) => setData('is_default', e.target.checked)}
                                />
                                <label className="custom-control-label pt-1 pl-3" htmlFor="is_default">
                                    {__('tenant.is_default_list')}
                                </label>
                            </div>
                            <div className="form-group custom-control custom-switch custom-switch-md">
                                <input
                                    id="is_active"
                                    type="checkbox"
                                    className="custom-control-input"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                />
                                <label className="custom-control-label pt-1 pl-3" htmlFor="is_active">
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
