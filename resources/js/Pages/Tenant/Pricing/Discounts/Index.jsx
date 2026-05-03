import MainLayout from '@/Layouts/Tenant/MainLayout.jsx';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, router } from '@inertiajs/react';

export default function TenantDiscountsIndex({ discounts, filters }) {
    const apply = (patch) => {
        router.get(route('discounts.index'), { ...filters, ...patch }, { preserveState: true });
    };

    return (
        <MainLayout title="tenant.discounts" name="tenant.discounts">
            <Head title={__('tenant.discounts')} />

            <section className="content">
                <div className="container-fluid">
                    <div className="row mb-3">
                        <div className="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h1 className="h4 m-0">{__('tenant.discounts')}</h1>
                            <Link href={route('discounts.create')} className="btn btn-primary btn-sm">
                                {__('tenant.add_discount')}
                            </Link>
                        </div>
                    </div>

                    <div className="card card-outline card-primary">
                        <div className="card-body">
                            <div className="row g-2 mb-3">
                                <div className="col-md-4">
                                    <input
                                        type="search"
                                        className="form-control form-control-sm"
                                        placeholder={__('tenant.search')}
                                        defaultValue={filters.search}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter') {
                                                apply({ search: e.target.value });
                                            }
                                        }}
                                    />
                                </div>
                                <div className="col-md-3">
                                    <select
                                        className="form-control form-control-sm"
                                        value={filters.status}
                                        onChange={(e) => apply({ status: e.target.value })}
                                    >
                                        <option value="all">{__('tenant.all')}</option>
                                        <option value="active">{__('tenant.active')}</option>
                                        <option value="inactive">{__('tenant.no_active')}</option>
                                    </select>
                                </div>
                            </div>

                            <div className="table-responsive">
                                <table className="table table-striped table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>{__('tenant.name')}</th>
                                            <th>{__('tenant.discount_type')}</th>
                                            <th>{__('tenant.discount_value')}</th>
                                            <th>{__('tenant.discount_scope')}</th>
                                            <th>{__('tenant.status')}</th>
                                            <th className="text-end">{__('tenant.action')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {discounts.data.map((row) => (
                                            <tr key={row.id}>
                                                <td>{row.name}</td>
                                                <td>{typeof row.discount_type === 'object' ? row.discount_type?.value : row.discount_type}</td>
                                                <td>{row.discount_value}</td>
                                                <td>
                                                    {typeof row.discount_scope === 'object'
                                                        ? row.discount_scope?.value
                                                        : row.discount_scope}
                                                </td>
                                                <td>{row.is_active ? __('tenant.active') : __('tenant.no_active')}</td>
                                                <td className="text-end">
                                                    <Link
                                                        href={route('discounts.edit', row.id)}
                                                        className="btn btn-link btn-sm py-0"
                                                    >
                                                        {__('tenant.edit')}
                                                    </Link>
                                                    <button
                                                        type="button"
                                                        className="btn btn-link btn-sm text-danger py-0"
                                                        onClick={() => {
                                                            if (confirm(__('tenant.confirm_delete'))) {
                                                                router.delete(route('discounts.destroy', row.id));
                                                            }
                                                        }}
                                                    >
                                                        {__('tenant.delete')}
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="d-flex flex-wrap gap-1 mt-2">
                                {discounts.links.map((link, idx) => (
                                    <Link
                                        key={idx}
                                        href={link.url || '#'}
                                        className={`btn btn-sm ${link.active ? 'btn-primary' : 'btn-outline-secondary'}`}
                                        preserveState
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </MainLayout>
    );
}
