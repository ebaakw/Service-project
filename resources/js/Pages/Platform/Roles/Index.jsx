import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import DataTable from '@/Components/ui/DataTable';
import Pagination from '@/Components/ui/Pagination';
import TableCard from '@/Components/ui/TableCard';
import TableToolbar from '@/Components/ui/TableToolbar';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function PlatformRolesIndex({ roles, filters, can }) {
    const { locale, direction } = usePage().props;
    const isRtl = direction === 'rtl';
    const isAr = (locale || '').toLowerCase().startsWith('ar');

    const [search, setSearch] = useState(filters?.search ?? '');
    const [isFiltering, setIsFiltering] = useState(false);
    const [perPage, setPerPage] = useState(Number(filters?.per_page ?? 10) || 10);
    const [visibleColumnIds, setVisibleColumnIds] = useState([]);
    const hasActiveFilters = Boolean((filters?.search ?? '').trim());

    const applyFilters = () => {
        setIsFiltering(true);
        router.get(
            route('platform.roles.index'),
            { search: search || undefined, per_page: perPage || undefined },
            { preserveState: true, preserveScroll: true, replace: true, onFinish: () => setIsFiltering(false) },
        );
    };

    const destroy = (id) => {
        if (!confirm(__('tenant.confirm_delete'))) return;
        router.delete(route('platform.roles.destroy', id), { preserveScroll: true });
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
                render: (r) => (
                    <div className="d-flex align-items-center gap-2">
                        <span dir="ltr">{r.name}</span>
                        {r.is_system ? (
                            <span className="badge badge-warning">{__('tenant.system_role')}</span>
                        ) : null}
                    </div>
                ),
                searchText: (r) => `${r.name ?? ''} ${r.display_name ?? ''}`.trim(),
            },
            {
                id: 'display_name',
                key: 'display_name',
                label: __('tenant.roles'),
                header: __('tenant.roles'),
                render: (r) => r.display_name ?? '—',
                searchText: (r) => String(r.display_name ?? ''),
            },
            {
                id: 'actions',
                key: 'actions',
                label: __('tenant.action'),
                header: <span className="d-block text-end">{__('tenant.action')}</span>,
                className: 'text-end',
                render: (r) =>
                    r.can_edit || r.can_delete ? (
                        <div className="btn-group">
                            {r.can_edit ? (
                                <Link
                                    href={route('platform.roles.edit', r.id)}
                                    className="btn btn-outline-primary btn-sm"
                                >
                                    <i className="fas fa-edit" />
                                </Link>
                            ) : null}
                            {r.can_delete ? (
                                <button
                                    type="button"
                                    className="btn btn-outline-danger btn-sm"
                                    onClick={() => destroy(r.id)}
                                >
                                    <i className="fas fa-trash" />
                                </button>
                            ) : null}
                        </div>
                    ) : null,
            },
        ],
        [destroy],
    );

    const resolvedVisibleColumnIds =
        visibleColumnIds.length > 0 ? visibleColumnIds : tableColumns.map((c) => c.id);
    const visibleTableColumns = tableColumns.filter((c) => resolvedVisibleColumnIds.includes(c.id));

    return (
        <PlatformAdminLayout breadcrumbKey="dashboard.nav.users_and_roles">
            <Head title={__('tenant.view_roles')} />

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
                            applyFilters();
                        }}
                    >
                        <div className="col-sm-12 col-md-6 col-lg-6">
                            <input
                                className="form-control"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder={isAr ? 'بحث بالاسم أو الاسم المعروض...' : 'Search by name or display name...'}
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
                                    router.get(route('platform.roles.index'), {}, {
                                        preserveState: true,
                                        preserveScroll: true,
                                        replace: true,
                                        onFinish: () => setIsFiltering(false),
                                    });
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
                title={__('tenant.view_roles')}
                actions={
                    can?.create ? (
                        <Link href={route('platform.roles.create')} className="btn btn-success btn-sm">
                            <i className="fas fa-plus mr-1" />
                            {__('tenant.add_roles')}
                        </Link>
                    ) : null
                }
                actionsVariant="card-tools"
                bodyPadding={false}
            >
                <div className="p-3 border-bottom">
                    <TableToolbar
                        rows={roles.data}
                        columns={tableColumns}
                        showSearch={false}
                        exportFilename="platform-roles"
                        persistKey="platform.roles.index"
                        onVisibleColumnIdsChange={(ids) => setVisibleColumnIds(ids)}
                        showPerPage
                        perPage={perPage}
                        perPageOptions={[10, 25, 50, 100]}
                        onPerPageChange={(value) => {
                            const next = Number(value) || 10;
                            setPerPage(next);
                            setIsFiltering(true);
                            router.get(
                                route('platform.roles.index'),
                                { search: search || undefined, per_page: next },
                                { preserveState: true, preserveScroll: true, replace: true, onFinish: () => setIsFiltering(false) },
                            );
                        }}
                    />
                </div>

                <DataTable
                    columns={visibleTableColumns}
                    rows={roles.data}
                    rowKey={(r) => r.id}
                    emptyTitle={__('tenant.no_data_found')}
                />

                <div className="px-3 pb-3">
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
            </TableCard>
        </PlatformAdminLayout>
    );
}

