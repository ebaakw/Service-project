import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, useForm } from '@inertiajs/react';

export default function TenantPriceListsCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name_ar: '',
        name_en: '',
        description_ar: '',
        description_en: '',
        is_default: false,
        starts_at: '',
        ends_at: '',
        is_active: true,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('price-lists.store'));
    };

    return (
        <CompanyDashboardLayout breadcrumbKey="dashboard.nav.company_price_lists">
            <Head title={__('tenant.add_price_list')} />

            <section className="content">
                <div className="container-fluid">
                    <div className="card card-outline card-primary">
                        <div className="card-header">
                            <h3 className="card-title">{__('tenant.add_price_list')}</h3>
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
                            <div className="form-group form-check">
                                <input
                                    id="is_default"
                                    type="checkbox"
                                    className="form-check-input"
                                    checked={data.is_default}
                                    onChange={(e) => setData('is_default', e.target.checked)}
                                />
                                <label className="form-check-label" htmlFor="is_default">
                                    {__('tenant.is_default_list')}
                                </label>
                            </div>
                            <div className="form-group form-check">
                                <input
                                    id="is_active"
                                    type="checkbox"
                                    className="form-check-input"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                />
                                <label className="form-check-label" htmlFor="is_active">
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
