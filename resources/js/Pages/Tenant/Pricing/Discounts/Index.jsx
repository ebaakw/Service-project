import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import DataTable from '@/Components/ui/DataTable';
import TableCard from '@/Components/ui/TableCard';
import StatusBadge from '@/Components/ui/StatusBadge';
import Pagination from '@/Layouts/Tenant/components/pagination/Pagination.jsx';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, router } from '@inertiajs/react';

export default function TenantDiscountsIndex({ discounts, filters }) {
    const apply = (patch) => {
        router.get(route('discounts.index'), { ...filters, ...patch }, { preserveState: true });
    };

    return (
        <CompanyDashboardLayout breadcrumbKey="dashboard.nav.company_discounts">
            <Head title={__('tenant.discounts')} />

            <section className="content">
                <div className="container-fluid">
                    <TableCard
                        title={__('tenant.discounts')}
                        actions={
                            <Link href={route('discounts.create')} className="btn btn-primary btn-sm">
                                {__('tenant.add_discount')}
                            </Link>
                        }
                        bodyPadding={false}
                    >
                        <div className="p-3">
                            <div className="row g-2">
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
                        </div>

                        <DataTable
                            columns={[
                                { key: 'name', header: __('tenant.name'), render: (r) => r.name },
                                {
                                    key: 'type',
                                    header: __('tenant.discount_type'),
                                    render: (r) =>
                                        typeof r.discount_type === 'object'
                                            ? r.discount_type?.value
                                            : r.discount_type,
                                },
                                { key: 'value', header: __('tenant.discount_value'), render: (r) => r.discount_value },
                                {
                                    key: 'scope',
                                    header: __('tenant.discount_scope'),
                                    render: (r) =>
                                        typeof r.discount_scope === 'object'
                                            ? r.discount_scope?.value
                                            : r.discount_scope,
                                },
                                {
                                    key: 'status',
                                    header: __('tenant.status'),
                                    render: (r) =>
                                        r.is_active ? (
                                            <StatusBadge variant="success">{__('tenant.active')}</StatusBadge>
                                        ) : (
                                            <StatusBadge variant="secondary">{__('tenant.no_active')}</StatusBadge>
                                        ),
                                },
                                {
                                    key: 'actions',
                                    header: <span className="d-block text-end">{__('tenant.action')}</span>,
                                    className: 'text-end',
                                    render: (r) => (
                                        <div className="btn-group btn-group-sm">
                                            <Link
                                                href={route('discounts.edit', r.id)}
                                                className="btn btn-outline-primary"
                                            >
                                                {__('tenant.edit')}
                                            </Link>
                                            <button
                                                type="button"
                                                className="btn btn-outline-danger"
                                                onClick={() => {
                                                    if (confirm(__('tenant.confirm_delete'))) {
                                                        router.delete(route('discounts.destroy', r.id));
                                                    }
                                                }}
                                            >
                                                {__('tenant.delete')}
                                            </button>
                                        </div>
                                    ),
                                },
                            ]}
                            rows={discounts.data}
                            rowKey={(r) => r.id}
                            emptyTitle={__('tenant.no_results') !== 'tenant.no_results' ? __('tenant.no_results') : '—'}
                        />

                        <div className="px-3 pb-3">
                            <Pagination links={discounts.links} data={discounts} />
                        </div>
                    </TableCard>
                </div>
            </section>
        </CompanyDashboardLayout>
    );
}
