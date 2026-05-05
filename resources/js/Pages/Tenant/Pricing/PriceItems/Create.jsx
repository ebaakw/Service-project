import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';

const PRICE_TYPES = ['fixed', 'starting_from', 'hourly', 'custom_quote'];

export default function TenantPriceItemsCreate({ priceLists, categories, defaultPriceListId }) {
    const [catId, setCatId] = useState(() => {
        const first = categories[0];
        return first ? first.id : '';
    });

    const problems = useMemo(() => {
        const c = categories.find((x) => x.id === Number(catId));
        return c?.problems ?? [];
    }, [categories, catId]);

    const { data, setData, post, processing, errors } = useForm({
        company_price_list_id: defaultPriceListId ? Number(defaultPriceListId) : priceLists?.[0]?.id ?? '',
        service_category_id: categories[0]?.id ?? '',
        service_problem_id: '',
        name_ar: '',
        name_en: '',
        description_ar: '',
        description_en: '',
        price_type: 'fixed',
        price: '',
        minimum_price: '',
        inspection_fee: '',
        warranty_days: '',
        warranty_description_ar: '',
        warranty_description_en: '',
        duration_minutes: '',
        sort_order: 0,
        is_active: true,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('price-items.store'));
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
                            <Link href={route('price-items.index')} className="btn btn-tool btn-sm">
                                ←
                            </Link>
                        </div>
                        <form onSubmit={submit} className="card-body">
                            <div className="form-group">
                                <label>{__('tenant.select_price_list')}</label>
                                <select
                                    className="form-control"
                                    value={data.company_price_list_id}
                                    onChange={(e) => setData('company_price_list_id', Number(e.target.value))}
                                >
                                    {priceLists.map((pl) => (
                                        <option key={pl.id} value={pl.id}>
                                            {pl.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.company_price_list_id && (
                                    <span className="text-danger small d-block">{errors.company_price_list_id}</span>
                                )}
                            </div>

                            <div className="form-group">
                                <label>{__('tenant.select_category')}</label>
                                <select
                                    className="form-control"
                                    value={data.service_category_id}
                                    onChange={(e) => {
                                        const v = Number(e.target.value);
                                        setCatId(v);
                                        setData('service_category_id', v);
                                        setData('service_problem_id', '');
                                    }}
                                >
                                    {categories.map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.name_ar}
                                        </option>
                                    ))}
                                </select>
                                {errors.service_category_id && (
                                    <span className="text-danger small d-block">{errors.service_category_id}</span>
                                )}
                            </div>

                            <div className="form-group">
                                <label>{__('tenant.select_problem')}</label>
                                <select
                                    className="form-control"
                                    value={data.service_problem_id}
                                    onChange={(e) =>
                                        setData('service_problem_id', e.target.value ? Number(e.target.value) : '')
                                    }
                                >
                                    <option value="">—</option>
                                    {problems.map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.title_ar}
                                        </option>
                                    ))}
                                </select>
                                {errors.service_problem_id && (
                                    <span className="text-danger small d-block">{errors.service_problem_id}</span>
                                )}
                            </div>

                            <div className="form-group">
                                <label>{__('tenant.item_name_ar')}</label>
                                <input
                                    className="form-control"
                                    value={data.name_ar}
                                    onChange={(e) => setData('name_ar', e.target.value)}
                                />
                                {errors.name_ar && <span className="text-danger small d-block">{errors.name_ar}</span>}
                            </div>
                            <div className="form-group">
                                <label>{__('tenant.item_name_en')}</label>
                                <input
                                    className="form-control"
                                    value={data.name_en}
                                    onChange={(e) => setData('name_en', e.target.value)}
                                />
                                {errors.name_en && <span className="text-danger small d-block">{errors.name_en}</span>}
                            </div>

                            <div className="form-group">
                                <label>{__('tenant.description')} (AR)</label>
                                <textarea
                                    className="form-control"
                                    rows={2}
                                    value={data.description_ar}
                                    onChange={(e) => setData('description_ar', e.target.value)}
                                />
                            </div>
                            <div className="form-group">
                                <label>{__('tenant.description')} (EN)</label>
                                <textarea
                                    className="form-control"
                                    rows={2}
                                    value={data.description_en}
                                    onChange={(e) => setData('description_en', e.target.value)}
                                />
                            </div>

                            <div className="form-group">
                                <label>{__('tenant.price_type')}</label>
                                <select
                                    className="form-control"
                                    value={data.price_type}
                                    onChange={(e) => setData('price_type', e.target.value)}
                                >
                                    {PRICE_TYPES.map((t) => (
                                    <option key={t} value={t}>
                                        {t}
                                    </option>
                                ))}
                                </select>
                                {errors.price_type && <span className="text-danger small d-block">{errors.price_type}</span>}
                            </div>

                            <div className="form-row">
                                <div className="form-group col-md-4">
                                    <label>{__('tenant.price')}</label>
                                    <input
                                        type="number"
                                        step="0.001"
                                        className="form-control"
                                        value={data.price}
                                        onChange={(e) => setData('price', e.target.value)}
                                    />
                                    {errors.price && <span className="text-danger small d-block">{errors.price}</span>}
                                </div>
                                <div className="form-group col-md-4">
                                    <label>{__('tenant.minimum_price')}</label>
                                    <input
                                        type="number"
                                        step="0.001"
                                        className="form-control"
                                        value={data.minimum_price}
                                        onChange={(e) => setData('minimum_price', e.target.value)}
                                    />
                                </div>
                                <div className="form-group col-md-4">
                                    <label>{__('tenant.inspection_fee')}</label>
                                    <input
                                        type="number"
                                        step="0.001"
                                        className="form-control"
                                        value={data.inspection_fee}
                                        onChange={(e) => setData('inspection_fee', e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="form-row">
                                <div className="form-group col-md-4">
                                    <label>{__('tenant.warranty_days')}</label>
                                    <input
                                        type="number"
                                        className="form-control"
                                        value={data.warranty_days}
                                        onChange={(e) => setData('warranty_days', e.target.value)}
                                    />
                                </div>
                                <div className="form-group col-md-4">
                                    <label>{__('tenant.duration_minutes')}</label>
                                    <input
                                        type="number"
                                        className="form-control"
                                        value={data.duration_minutes}
                                        onChange={(e) => setData('duration_minutes', e.target.value)}
                                    />
                                </div>
                            </div>
                            <div className="form-group">
                                <label>{__('tenant.warranty_desc_ar')}</label>
                                <textarea
                                    className="form-control"
                                    rows={2}
                                    value={data.warranty_description_ar}
                                    onChange={(e) => setData('warranty_description_ar', e.target.value)}
                                />
                            </div>
                            <div className="form-group">
                                <label>{__('tenant.warranty_desc_en')}</label>
                                <textarea
                                    className="form-control"
                                    rows={2}
                                    value={data.warranty_description_en}
                                    onChange={(e) => setData('warranty_description_en', e.target.value)}
                                />
                            </div>

                            <div className="form-group">
                                <label>{__('tenant.sort_order')}</label>
                                <input
                                    type="number"
                                    className="form-control"
                                    value={data.sort_order}
                                    onChange={(e) => setData('sort_order', Number(e.target.value))}
                                />
                            </div>

                            <div className="form-group form-check">
                                <input
                                    id="pi_active"
                                    type="checkbox"
                                    className="form-check-input"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                />
                                <label className="form-check-label" htmlFor="pi_active">
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
