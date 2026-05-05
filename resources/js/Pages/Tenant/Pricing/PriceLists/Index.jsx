import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, router } from '@inertiajs/react';

export default function TenantPriceListsIndex({ priceLists, filters }) {
    const apply = (patch) => {
        router.get(route('price-lists.index'), { ...filters, ...patch }, { preserveState: true });
    };

    return (
        <CompanyDashboardLayout breadcrumbKey="dashboard.nav.company_price_lists">
            <Head title={__('tenant.price_lists')} />

            <div className="card card-outline card-primary">
                <div className="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h3 className="card-title m-0">{__('tenant.price_lists')}</h3>
                    <Link href={route('price-lists.create')} className="btn btn-primary btn-sm">
                        {__('tenant.add_price_list')}
                    </Link>
                </div>

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
                                <option value="all">{__('tenant.all')} </option>
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
                                    <th>{__('tenant.description')}</th>
                                    <th>{__('tenant.is_default_list')}</th>
                                    <th>{__('tenant.date_start')}</th>
                                    <th>{__('tenant.date_end')}</th>
                                    <th>{__('tenant.status')}</th>
                                    <th className="text-end">{__('tenant.action')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {priceLists.data.map((row) => (
                                    <tr key={row.id}>
                                        <td>{row.name}</td>
                                        <td>{row.description ?? '—'}</td>
                                        <td>{row.is_default ? __('tenant.active') : '—'}</td>
                                        <td>{row.starts_at ? row.starts_at : '—'}</td>
                                        <td>{row.ends_at ? row.ends_at : '—'}</td>
                                        <td>{row.is_active ? __('tenant.active') : __('tenant.no_active')}</td>
                                        <td className="text-end">
                                            <Link href={route('price-lists.edit', row.id)} className="btn btn-link btn-sm py-0">
                                                {__('tenant.edit')}
                                            </Link>
                                            <button
                                                type="button"
                                                className="btn btn-link btn-sm text-danger py-0"
                                                onClick={() => {
                                                    if (confirm(__('tenant.confirm_delete'))) {
                                                        router.delete(route('price-lists.destroy', row.id));
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
                        {priceLists.links.map((link, idx) => (
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
        </CompanyDashboardLayout>
    );
}
