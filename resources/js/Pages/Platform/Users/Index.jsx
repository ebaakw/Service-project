import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import DataTable from '@/Components/ui/DataTable';
import Pagination from '@/Components/ui/Pagination';
import TableCard from '@/Components/ui/TableCard';
import TableToolbar from '@/Components/ui/TableToolbar';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function PlatformUsersIndex({ users, filters, can }) {
    const { locale, direction } = usePage().props;
    const isRtl = direction === 'rtl';
    const isAr = (locale || '').toLowerCase().startsWith('ar');

    const showDeleted = Boolean(filters?.show_deleted);
    const [search, setSearch] = useState(filters?.search ?? '');
    const [isFiltering, setIsFiltering] = useState(false);
    const [perPage, setPerPage] = useState(Number(filters?.per_page ?? 10) || 10);
    const [visibleColumnIds, setVisibleColumnIds] = useState([]);
    const hasActiveFilters = Boolean((filters?.search ?? '').trim());

    const apply = (patch) => {
        setIsFiltering(true);
        router.get(
            route('platform.users.index'),
            { search: search || undefined, show_deleted: showDeleted ? 1 : undefined, per_page: perPage || undefined, ...patch },
            { preserveState: true, preserveScroll: true, replace: true, onFinish: () => setIsFiltering(false) },
        );
    };

    const destroy = (id) => {
        if (!confirm(__('tenant.confirm_delete'))) return;
        router.delete(route('platform.users.destroy', id), { preserveScroll: true });
    };

    const restore = (id) => {
        if (!confirm(__('tenant.restore_account_confirm'))) return;
        router.post(route('platform.users.restore', id), {}, { preserveScroll: true });
    };

    const tableColumns = useMemo(
        () => [
            {
                id: 'id',
                key: 'id',
                label: __('tenant.id'),
                header: __('tenant.id'),
                render: (r) => r.id,
                searchText: (r) => String(r.id ?? ''),
            },
            {
                id: 'name',
                key: 'name',
                label: __('tenant.name'),
                header: __('tenant.name'),
                render: (r) => <strong>{r.name_ar || r.name || '—'}</strong>,
                searchText: (r) => `${r.name ?? ''} ${r.name_ar ?? ''} ${r.name_en ?? ''}`.trim(),
            },
            {
                id: 'email',
                key: 'email',
                label: __('tenant.email'),
                header: __('tenant.email'),
                render: (r) => <span dir="ltr">{r.email}</span>,
                searchText: (r) => String(r.email ?? ''),
            },
            {
                id: 'phone',
                key: 'phone',
                label: __('tenant.phone'),
                header: __('tenant.phone'),
                render: (r) => <span dir="ltr">{r.phone || '—'}</span>,
                searchText: (r) => String(r.phone ?? ''),
            },
            {
                id: 'status',
                key: 'status',
                label: __('tenant.status'),
                header: __('tenant.status'),
                render: (r) =>
                    r.status === 'active' ? (
                        <span className="badge bg-success">{__('tenant.active')}</span>
                    ) : r.status === 'suspended' ? (
                        <span className="badge bg-danger">{__('tenant.suspended')}</span>
                    ) : (
                        <span className="badge bg-secondary">{__('tenant.no_active')}</span>
                    ),
                searchText: (r) => String(r.status ?? ''),
            },
            {
                id: 'actions',
                key: 'actions',
                label: __('tenant.action'),
                header: <span className="d-block text-end">{__('tenant.action')}</span>,
                className: 'text-end',
                render: (r) =>
                    showDeleted ? (
                        r.can_edit ? (
                            <button type="button" className="btn btn-info btn-sm" onClick={() => restore(r.id)}>
                                <i className="fas fa-undo" />
                                <span className="mx-1">{__('tenant.restore')}</span>
                            </button>
                        ) : null
                    ) : (
                        <div className="btn-group">
                            {r.can_edit ? (
                                <Link href={route('platform.users.edit', r.id)} className="btn btn-outline-primary btn-sm">
                                    <i className="fas fa-edit" />
                                </Link>
                            ) : null}
                            {r.can_delete ? (
                                <button type="button" className="btn btn-outline-danger btn-sm" onClick={() => destroy(r.id)}>
                                    <i className="fas fa-trash" />
                                </button>
                            ) : null}
                        </div>
                    ),
            },
        ],
        [showDeleted, restore, destroy],
    );

    const resolvedVisibleColumnIds =
        visibleColumnIds.length > 0 ? visibleColumnIds : tableColumns.map((c) => c.id);
    const visibleTableColumns = tableColumns.filter((c) => resolvedVisibleColumnIds.includes(c.id));

    return (
        <PlatformAdminLayout breadcrumbKey="dashboard.nav.users_and_roles">
            <Head title={__('tenant.view_users')} />

            <div className={hasActiveFilters ? 'card card-outline card-primary' : 'card card-outline card-primary collapsed-card'}>
                <div className="card-header" data-card-widget="collapse" style={{ cursor: 'pointer' }}>
                    <h3 className="card-title">
                        <span>
                            <i className="fas fa-filter" />
                        </span>
                        <span className="mx-1">{__('tenant.filter')}</span>
                    </h3>
                    <div className="card-tools">
                        <button type="button" className="btn btn-tool">
                            <i className="fas fa-plus" />
                        </button>
                    </div>
                </div>
                <div className="card-body" style={hasActiveFilters ? undefined : { display: 'none' }}>
                    <form
                        className="row"
                        onSubmit={(e) => {
                            e.preventDefault();
                            apply({});
                        }}
                    >
                        <div className="col-sm-12 col-md-6 col-lg-6">
                            <input
                                className="form-control"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder={isAr ? 'بحث بالاسم أو البريد أو الهاتف...' : 'Search by name, email, or phone...'}
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
                                    setPerPage(10);
                                    setIsFiltering(true);
                                    router.get(
                                        route('platform.users.index'),
                                        { show_deleted: showDeleted ? 1 : undefined },
                                        { preserveState: true, preserveScroll: true, replace: true, onFinish: () => setIsFiltering(false) },
                                    );
                                }}
                                disabled={isFiltering}
                            >
                                <i className={isRtl ? 'fas fa-undo ms-1' : 'fas fa-undo me-1'} />
                                {__('tenant.reset')}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <TableCard
                title={__('tenant.view_users')}
                actions={
                    <div className="d-flex align-items-center gap-2 flex-wrap">
                        <ul className="nav nav-tabs">
                            <li className="nav-item">
                                <Link
                                    className={`nav-link ${!showDeleted ? 'active' : ''}`}
                                    href={route('platform.users.index')}
                                >
                                    {__('tenant.active_accounts')}
                                </Link>
                            </li>
                            <li className="nav-item">
                                <Link
                                    className={`nav-link ${showDeleted ? 'active' : ''}`}
                                    href={route('platform.users.index', { show_deleted: 1 })}
                                >
                                    {__('tenant.deleted_accounts')}
                                </Link>
                            </li>
                        </ul>

                        {can?.create && !showDeleted ? (
                            <Link href={route('platform.users.create')} className="btn btn-success btn-sm">
                                <i className="fas fa-plus mr-1" />
                                {__('tenant.add_user')}
                            </Link>
                        ) : null}
                    </div>
                }
                actionsVariant="card-tools"
                bodyPadding={false}
            >
                <div className="p-3 border-bottom">
                    <TableToolbar
                        rows={users.data}
                        columns={tableColumns}
                        showSearch={false}
                        exportFilename={`platform-users${showDeleted ? '-deleted' : ''}`}
                        persistKey={`platform.users.index.${showDeleted ? 'deleted' : 'active'}`}
                        onVisibleColumnIdsChange={(ids) => setVisibleColumnIds(ids)}
                        showPerPage
                        perPage={perPage}
                        perPageOptions={[10, 25, 50, 100]}
                        onPerPageChange={(value) => {
                            const next = Number(value) || 10;
                            setPerPage(next);
                            setIsFiltering(true);
                            router.get(
                                route('platform.users.index'),
                                { search: search || undefined, show_deleted: showDeleted ? 1 : undefined, per_page: next },
                                { preserveState: true, preserveScroll: true, replace: true, onFinish: () => setIsFiltering(false) },
                            );
                        }}
                    />
                </div>

                <DataTable
                    columns={visibleTableColumns}
                    rows={users.data}
                    rowKey={(r) => r.id}
                    emptyTitle={__('tenant.no_data_found')}
                />

                <div className="px-3 pb-3">
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
            </TableCard>
        </PlatformAdminLayout>
    );
}

