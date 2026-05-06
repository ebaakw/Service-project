import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import Pagination from '@/Components/ui/Pagination';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function CompanyRolesIndex({ roles, filters, can }) {
    const { direction } = usePage().props;
    const isRtl = direction === 'rtl';
    const [search, setSearch] = useState(filters?.search ?? '');
    const [isFiltering, setIsFiltering] = useState(false);

    const applyFilters = () => {
        setIsFiltering(true);
        router.get(
            route('company.roles.index'),
            { search: search || undefined },
            { preserveState: true, preserveScroll: true, replace: true, onFinish: () => setIsFiltering(false) },
        );
    };

    const destroy = (id) => {
        if (!confirm(__('tenant.confirm_delete'))) return;
        router.delete(route('company.roles.destroy', id), { preserveScroll: true });
    };

    return (
        <CompanyDashboardLayout breadcrumbKey="dashboard.nav.users_and_roles">
            <Head title={__('tenant.view_roles')} />

            <div className="card card-outline card-primary">
                <div className="card-header d-flex align-items-center justify-content-between flex-wrap">
                    <h3 className="card-title m-0">{__('tenant.view_roles')}</h3>
                    {can?.create && (
                        <Link href={route('company.roles.create')} className="btn btn-success btn-sm">
                            <i className="fas fa-plus mr-1" />
                            {__('tenant.add_roles')}
                        </Link>
                    )}
                </div>

                <div className="card-body">
                    <form
                        className="row"
                        onSubmit={(e) => {
                            e.preventDefault();
                            applyFilters();
                        }}
                    >
                        <div className="col-sm-12 col-md-6">
                            <input
                                className="form-control"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder={__('tenant.search')}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            />
                        </div>
                        <div className="col-sm-12 mt-3" style={{ textAlign: isRtl ? 'right' : 'left' }}>
                            <button type="submit" className="btn btn-primary px-4" disabled={isFiltering}>
                                <i className={isRtl ? 'fas fa-filter ms-1' : 'fas fa-filter me-1'} />
                                {__('tenant.filter')}
                            </button>
                            <button
                                type="button"
                                className="btn btn-danger px-4 mx-2"
                                onClick={() => {
                                    setSearch('');
                                    setIsFiltering(true);
                                    router.get(route('company.roles.index'), {}, { preserveState: true, replace: true, onFinish: () => setIsFiltering(false) });
                                }}
                                disabled={isFiltering}
                            >
                                <i className={isRtl ? 'fas fa-undo ms-1' : 'fas fa-undo me-1'} />
                                {__('tenant.reset')}
                            </button>
                        </div>
                    </form>

                    <div className="table-responsive mt-3">
                        <table className="table table-hover table-bordered table-head-fixed">
                            <thead>
                                <tr>
                                    <th>{__('tenant.id')}</th>
                                    <th>{__('tenant.name')}</th>
                                    <th>{__('tenant.roles')}</th>
                                    <th className="text-end">{__('tenant.action')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {roles.data.map((r) => (
                                    <tr key={r.id}>
                                        <td>{r.id}</td>
                                        <td>
                                            <div className="d-flex align-items-center gap-2">
                                                <span dir="ltr">{r.name}</span>
                                                {r.is_system ? (
                                                    <span className="badge badge-warning">{__('tenant.system_role')}</span>
                                                ) : null}
                                            </div>
                                        </td>
                                        <td>{r.display_name}</td>
                                        <td className="text-end" style={{ width: 150 }}>
                                            {r.can_edit || r.can_delete ? (
                                                <div className="btn-group">
                                                    {r.can_edit ? (
                                                        <Link href={route('company.roles.edit', r.id)} className="btn btn-outline-primary btn-sm">
                                                            <i className="fas fa-edit" />
                                                        </Link>
                                                    ) : null}
                                                    {r.can_delete ? (
                                                        <button type="button" className="btn btn-outline-danger btn-sm" onClick={() => destroy(r.id)}>
                                                            <i className="fas fa-trash" />
                                                        </button>
                                                    ) : null}
                                                </div>
                                            ) : null}
                                        </td>
                                    </tr>
                                ))}
                                {roles.data.length === 0 && (
                                    <tr>
                                        <td colSpan={4} className="text-center text-muted py-4">
                                            {__('tenant.no_data_found')}
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    <Pagination
                        links={roles.links}
                        data={{
                            current_page: roles.current_page,
                            last_page: roles.last_page,
                            prev_page_url: roles.prev_page_url,
                            next_page_url: roles.next_page_url,
                            from: roles.from,
                            to: roles.to,
                            total: roles.total,
                        }}
                    />
                </div>
            </div>
        </CompanyDashboardLayout>
    );
}

