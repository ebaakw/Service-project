import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import Pagination from '@/Components/ui/Pagination';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function CompanyUsersIndex({ users, filters, can }) {
    const { direction } = usePage().props;
    const isRtl = direction === 'rtl';

    const showDeleted = Boolean(filters?.show_deleted);
    const [search, setSearch] = useState(filters?.search ?? '');
    const [isFiltering, setIsFiltering] = useState(false);

    const apply = (patch) => {
        setIsFiltering(true);
        router.get(
            route('company.users.index'),
            { search: search || undefined, show_deleted: showDeleted ? 1 : undefined, ...patch },
            { preserveState: true, preserveScroll: true, replace: true, onFinish: () => setIsFiltering(false) },
        );
    };

    const destroy = (id) => {
        if (!confirm(__('tenant.confirm_delete'))) return;
        router.delete(route('company.users.destroy', id), { preserveScroll: true });
    };

    const restore = (id) => {
        if (!confirm(__('tenant.restore_account_confirm'))) return;
        router.post(route('company.users.restore', id), {}, { preserveScroll: true });
    };

    return (
        <CompanyDashboardLayout breadcrumbKey="dashboard.nav.users_and_roles">
            <Head title={__('tenant.view_users')} />

            <div className="card card-outline card-primary">
                <div className="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <ul className="nav nav-tabs card-header-tabs">
                        <li className="nav-item">
                            <Link className={`nav-link ${!showDeleted ? 'active' : ''}`} href={route('company.users.index')}>
                                {__('tenant.active_accounts')}
                            </Link>
                        </li>
                        <li className="nav-item">
                            <Link
                                className={`nav-link ${showDeleted ? 'active' : ''}`}
                                href={route('company.users.index', { show_deleted: 1 })}
                            >
                                {__('tenant.deleted_accounts')}
                            </Link>
                        </li>
                    </ul>

                    {can?.create && !showDeleted && (
                        <Link href={route('company.users.create')} className="btn btn-success btn-sm">
                            <i className="fas fa-plus mr-1" />
                            {__('tenant.add_user')}
                        </Link>
                    )}
                </div>

                <div className="card-body">
                    <form
                        className="row"
                        onSubmit={(e) => {
                            e.preventDefault();
                            apply({});
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
                                    router.get(route('company.users.index'), { show_deleted: showDeleted ? 1 : undefined }, { preserveState: true, replace: true, onFinish: () => setIsFiltering(false) });
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
                                    <th>{__('tenant.email')}</th>
                                    <th>{__('tenant.phone')}</th>
                                    <th>{__('tenant.roles')}</th>
                                    <th>{__('tenant.status')}</th>
                                    <th className="text-end">{__('tenant.action')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {users.data.map((u) => (
                                    <tr key={u.id}>
                                        <td>{u.id}</td>
                                        <td>{u.name_ar || u.name || '—'}</td>
                                        <td dir="ltr">{u.email}</td>
                                        <td dir="ltr">{u.phone || '—'}</td>
                                        <td>{u.role?.display_name ?? '—'}</td>
                                        <td>
                                            {u.status === 'active' ? (
                                                <span className="badge bg-success">{__('tenant.active')}</span>
                                            ) : (
                                                <span className="badge bg-secondary">{__('tenant.no_active')}</span>
                                            )}
                                        </td>
                                        <td className="text-end" style={{ width: 170 }}>
                                            {showDeleted ? (
                                                u.can_edit && (
                                                    <button type="button" className="btn btn-info btn-sm" onClick={() => restore(u.id)}>
                                                        <i className="fas fa-undo" />
                                                        <span className="mx-1">{__('tenant.restore')}</span>
                                                    </button>
                                                )
                                            ) : (
                                                <div className="btn-group">
                                                    {u.can_edit && (
                                                        <Link href={route('company.users.edit', u.id)} className="btn btn-outline-primary btn-sm">
                                                            <i className="fas fa-edit" />
                                                        </Link>
                                                    )}
                                                    {u.can_delete && (
                                                        <button type="button" className="btn btn-outline-danger btn-sm" onClick={() => destroy(u.id)}>
                                                            <i className="fas fa-trash" />
                                                        </button>
                                                    )}
                                                </div>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                                {users.data.length === 0 && (
                                    <tr>
                                        <td colSpan={7} className="text-center text-muted py-4">
                                            {__('tenant.no_data_found')}
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    <Pagination
                        links={users.links}
                        data={{
                            current_page: users.current_page,
                            last_page: users.last_page,
                            prev_page_url: users.prev_page_url,
                            next_page_url: users.next_page_url,
                            from: users.from,
                            to: users.to,
                            total: users.total,
                        }}
                    />
                </div>
            </div>
        </CompanyDashboardLayout>
    );
}

