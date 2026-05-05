import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, useForm } from '@inertiajs/react';

const DISCOUNT_TYPES = ['percentage', 'fixed_amount'];
const SCOPES = ['full_menu', 'category', 'price_item'];

export default function TenantDiscountsCreate({ categories, priceItems }) {
    const { data, setData, post, processing, errors, transform } = useForm({
        name_ar: '',
        name_en: '',
        description_ar: '',
        description_en: '',
        discount_type: 'percentage',
        discount_value: '',
        discount_scope: 'full_menu',
        starts_at: '',
        ends_at: '',
        max_discount_amount: '',
        min_order_amount: '',
        usage_limit: '',
        is_active: true,
        category_ids: [],
        company_price_item_ids: [],
    });

    const submit = (e) => {
        e.preventDefault();
        transform((raw) => ({
            ...raw,
            starts_at: raw.starts_at || null,
            ends_at: raw.ends_at || null,
            max_discount_amount: raw.max_discount_amount === '' ? null : raw.max_discount_amount,
            min_order_amount: raw.min_order_amount === '' ? null : raw.min_order_amount,
            usage_limit: raw.usage_limit === '' ? null : raw.usage_limit,
            category_ids: raw.discount_scope === 'category' ? raw.category_ids : [],
            company_price_item_ids: raw.discount_scope === 'price_item' ? raw.company_price_item_ids : [],
        }));
        post(route('discounts.store'));
    };

    const scope = data.discount_scope;

    return (
        <CompanyDashboardLayout breadcrumbKey="dashboard.nav.company_discounts">
            <Head title={__('tenant.add_discount')} />

            <section className="content">
                <div className="container-fluid">
                    <div className="card card-outline card-primary">
                        <div className="card-header">
                            <h3 className="card-title">{__('tenant.add_discount')}</h3>
                            <Link href={route('discounts.index')} className="btn btn-tool btn-sm">
                                ←
                            </Link>
                        </div>
                        <form onSubmit={submit} className="card-body">
                            <div className="form-row">
                                <div className="form-group col-md-6">
                                    <label>{__('tenant.discount_name_ar')}</label>
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
                                    <label>{__('tenant.discount_name_en')}</label>
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
                                        rows={2}
                                        value={data.description_ar}
                                        onChange={(e) => setData('description_ar', e.target.value)}
                                    />
                                </div>
                                <div className="form-group col-md-6">
                                    <label>{__('tenant.description_en')}</label>
                                    <textarea
                                        dir="ltr"
                                        className="form-control"
                                        rows={2}
                                        value={data.description_en}
                                        onChange={(e) => setData('description_en', e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="form-row">
                                <div className="form-group col-md-6">
                                    <label>{__('tenant.discount_type')}</label>
                                    <select
                                        className="form-control"
                                        value={data.discount_type}
                                        onChange={(e) => setData('discount_type', e.target.value)}
                                    >
                                        {DISCOUNT_TYPES.map((t) => (
                                            <option key={t} value={t}>
                                                {t}
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
                                        onChange={(e) => setData('discount_value', e.target.value)}
                                    />
                                    {errors.discount_value && (
                                        <span className="text-danger small d-block">{errors.discount_value}</span>
                                    )}
                                </div>
                            </div>

                            <div className="form-group">
                                <label>{__('tenant.discount_scope')}</label>
                                <select
                                    className="form-control"
                                    value={data.discount_scope}
                                    onChange={(e) => setData('discount_scope', e.target.value)}
                                >
                                    {SCOPES.map((s) => (
                                    <option key={s} value={s}>
                                        {s === 'full_menu'
                                            ? __('tenant.scope_full_menu')
                                            : s === 'category'
                                              ? __('tenant.scope_category')
                                              : __('tenant.scope_price_item')}
                                    </option>
                                ))}
                                </select>
                                {errors.discount_scope && (
                                    <span className="text-danger small d-block">{errors.discount_scope}</span>
                                )}
                            </div>

                            {scope === 'category' && (
                                <div className="form-group">
                                    <label>{__('tenant.select_categories')}</label>
                                    <select
                                        multiple
                                        className="form-control"
                                        size={Math.min(8, Math.max(4, categories.length))}
                                        value={data.category_ids.map(String)}
                                        onChange={(e) => {
                                            const opts = Array.from(e.target.selectedOptions).map((o) => Number(o.value));
                                            setData('category_ids', opts);
                                        }}
                                    >
                                        {categories.map((c) => (
                                            <option key={c.id} value={c.id}>
                                                {c.name_ar}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.category_ids && (
                                        <span className="text-danger small d-block">{errors.category_ids}</span>
                                    )}
                                </div>
                            )}

                            {scope === 'price_item' && (
                                <div className="form-group">
                                    <label>{__('tenant.select_price_items')}</label>
                                    <select
                                        multiple
                                        className="form-control"
                                        size={Math.min(10, Math.max(4, priceItems.length))}
                                        value={data.company_price_item_ids.map(String)}
                                        onChange={(e) => {
                                            const opts = Array.from(e.target.selectedOptions).map((o) => Number(o.value));
                                            setData('company_price_item_ids', opts);
                                        }}
                                    >
                                        {priceItems.map((p) => (
                                            <option key={p.id} value={p.id}>
                                                {p.name_ar}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.company_price_item_ids && (
                                        <span className="text-danger small d-block">{errors.company_price_item_ids}</span>
                                    )}
                                </div>
                            )}

                            <div className="form-row">
                                <div className="form-group col-md-6">
                                    <label>{__('tenant.date_start')}</label>
                                    <input
                                        type="datetime-local"
                                        className="form-control"
                                        value={data.starts_at}
                                        onChange={(e) => setData('starts_at', e.target.value)}
                                    />
                                </div>
                                <div className="form-group col-md-6">
                                    <label>{__('tenant.date_end')}</label>
                                    <input
                                        type="datetime-local"
                                        className="form-control"
                                        value={data.ends_at}
                                        onChange={(e) => setData('ends_at', e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="form-row">
                                <div className="form-group col-md-4">
                                    <label>{__('tenant.max_discount_amount')}</label>
                                    <input
                                        type="number"
                                        step="0.001"
                                        className="form-control"
                                        value={data.max_discount_amount}
                                        onChange={(e) => setData('max_discount_amount', e.target.value)}
                                    />
                                </div>
                                <div className="form-group col-md-4">
                                    <label>{__('tenant.min_order_amount')}</label>
                                    <input
                                        type="number"
                                        step="0.001"
                                        className="form-control"
                                        value={data.min_order_amount}
                                        onChange={(e) => setData('min_order_amount', e.target.value)}
                                    />
                                </div>
                                <div className="form-group col-md-4">
                                    <label>{__('tenant.usage_limit')}</label>
                                    <input
                                        type="number"
                                        className="form-control"
                                        value={data.usage_limit}
                                        onChange={(e) => setData('usage_limit', e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="form-group form-check">
                                <input
                                    id="disc_active"
                                    type="checkbox"
                                    className="form-check-input"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                />
                                <label className="form-check-label" htmlFor="disc_active">
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
