import DataTable from '@/Components/ui/DataTable';
import Pagination from '@/Components/ui/Pagination';
import StatusBadge from '@/Components/ui/StatusBadge';
import TableCard from '@/Components/ui/TableCard';
import type { TableToolbarColumn } from '@/Components/ui/TableToolbar';
import TableToolbar from '@/Components/ui/TableToolbar';
import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useCallback, useMemo, useState } from 'react';

type CategoryRow = {
    id: number;
    name_ar: string;
    name_en: string | null;
    slug: string;
    is_active: boolean;
    sort_order: number;
    can_update?: boolean;
    can_delete?: boolean;
};

type LinkItem = { url: string | null; label: string; active: boolean };
type Paginator<T> = {
    data: T[];
    links: LinkItem[];
    current_page?: number;
    last_page?: number;
    prev_page_url?: string | null;
    next_page_url?: string | null;
    from?: number | null;
    to?: number | null;
    total?: number | null;
};

export default function PlatformServiceCategoriesIndex({
    categories,
    filters,
    can,
}: {
    categories: Paginator<CategoryRow>;
    filters: { search: string; status: string; per_page?: number };
    can: { create?: boolean };
}) {
    const { direction } = usePage<PageProps>().props;
    const isRtl = direction === 'rtl';

    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');
    const [perPage, setPerPage] = useState<number>(Number(filters.per_page ?? 10) || 10);
    const [isFiltering, setIsFiltering] = useState(false);
    const [visibleColumnIds, setVisibleColumnIds] = useState<string[]>([]);

    const hasActiveFilters = Boolean(
        (filters.search ?? '').trim() || ((filters.status ?? 'all') !== 'all' && (filters.status ?? '') !== ''),
    );

    const applyFilters = () => {
        setIsFiltering(true);
        router.get(
            route('platform.service-categories.index'),
            {
                search: search || undefined,
                status: status === 'all' ? undefined : status,
                per_page: perPage || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => setIsFiltering(false),
            },
        );
    };

    const resetFilters = () => {
        setSearch('');
        setStatus('all');
        setPerPage(10);
        setIsFiltering(true);
        router.get(
            route('platform.service-categories.index'),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => setIsFiltering(false),
            },
        );
    };

    const destroy = useCallback((id: number) => {
        if (!confirm(__('dashboard.service_categories.delete_confirm'))) {
            return;
        }
        router.delete(route('platform.service-categories.destroy', id), { preserveScroll: true });
    }, []);

    const tableColumns: Array<
        TableToolbarColumn<CategoryRow> & { key: string; header: React.ReactNode; className?: string; headerClassName?: string }
    > = useMemo(
        () => [
            {
                id: 'name_ar',
                key: 'name_ar',
                label: __('dashboard.service_categories.col_name_ar'),
                header: __('dashboard.service_categories.col_name_ar'),
                render: (r) => <strong>{r.name_ar}</strong>,
                searchText: (r) => r.name_ar ?? '',
            },
            {
                id: 'name_en',
                key: 'name_en',
                label: __('dashboard.service_categories.col_name_en'),
                header: __('dashboard.service_categories.col_name_en'),
                render: (r) => <span>{r.name_en ?? '—'}</span>,
                searchText: (r) => r.name_en ?? '',
            },
            {
                id: 'slug',
                key: 'slug',
                label: __('dashboard.service_categories.col_slug'),
                header: __('dashboard.service_categories.col_slug'),
                render: (r) => (
                    <span dir="ltr" className="d-inline-block">
                        {r.slug}
                    </span>
                ),
                searchText: (r) => r.slug ?? '',
            },
            {
                id: 'sort_order',
                key: 'sort_order',
                label: __('dashboard.service_categories.col_sort'),
                header: __('dashboard.service_categories.col_sort'),
                render: (r) => <span dir="ltr">{r.sort_order}</span>,
                searchText: (r) => String(r.sort_order),
            },
            {
                id: 'is_active',
                key: 'is_active',
                label: __('dashboard.service_categories.col_active'),
                header: __('dashboard.service_categories.col_active'),
                render: (r) => (
                    <StatusBadge variant={r.is_active ? 'success' : 'secondary'} pill>
                        {r.is_active ? __('dashboard.service_categories.yes') : __('dashboard.service_categories.no')}
                    </StatusBadge>
                ),
                searchText: (r) =>
                    r.is_active ? __('dashboard.service_categories.yes') : __('dashboard.service_categories.no'),
            },
            {
                id: 'actions',
                key: 'actions',
                label: __('dashboard.service_categories.col_actions'),
                header: <span className="d-block text-end">{__('dashboard.service_categories.col_actions')}</span>,
                className: 'text-end',
                render: (r) => (
                    <div className="btn-group">
                        <Link
                            href={route('platform.service-categories.problems.index', r.id)}
                            className="btn btn-outline-info btn-sm"
                        >
                            {__('dashboard.service_categories.link_problems')}
                        </Link>
                        <Link
                            href={route('platform.service-categories.intake-fields.index', r.id)}
                            className="btn btn-outline-secondary btn-sm"
                        >
                            {__('dashboard.service_categories.link_intake')}
                        </Link>
                        {r.can_update ? (
                            <Link href={route('platform.service-categories.edit', r.id)} className="btn btn-outline-primary btn-sm">
                                {__('dashboard.actions.edit')}
                            </Link>
                        ) : null}
                        {r.can_delete ? (
                            <button type="button" className="btn btn-outline-danger btn-sm" onClick={() => destroy(r.id)}>
                                {__('dashboard.actions.delete')}
                            </button>
                        ) : null}
                    </div>
                ),
            },
        ],
        [__, destroy],
    );

    const resolvedVisibleColumnIds = visibleColumnIds.length > 0 ? visibleColumnIds : tableColumns.map((c) => c.id);
    const visibleTableColumns = tableColumns.filter((c) => resolvedVisibleColumnIds.includes(c.id));

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.global_service_catalog"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('dashboard.service_categories.page_title')}</h4>}
        >
            <Head title={__('dashboard.service_categories.page_title')} />

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
                <div className="card-body" style={hasActiveFilters ? undefined : ({ display: 'none' } as React.CSSProperties)}>
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
                                name="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder={__('dashboard.service_categories.search_placeholder')}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            />
                        </div>

                        <div className="col-sm-6 col-md-3 col-lg-3">
                            <select
                                className="custom-select"
                                name="status"
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            >
                                <option value="all">{__('dashboard.service_categories.status_all')}</option>
                                <option value="active">{__('dashboard.status.active')}</option>
                                <option value="inactive">{__('dashboard.status.inactive')}</option>
                            </select>
                        </div>

                        <div className="col-sm-12 mt-3" style={{ textAlign: isRtl ? 'right' : 'left' }}>
                            <button type="submit" className="btn btn-primary px-4" disabled={isFiltering}>
                                <i className={isRtl ? 'fas fa-filter ms-1' : 'fas fa-filter me-1'} />
                                {__('tenant.filter')}
                            </button>
                            <button
                                type="button"
                                className="btn btn-danger px-4 mx-2"
                                onClick={resetFilters}
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
                title={__('dashboard.service_categories.list_title')}
                actions={
                    can?.create ? (
                        <Link href={route('platform.service-categories.create')} className="btn btn-success btn-sm">
                            <i className={isRtl ? 'fas fa-plus ms-1' : 'fas fa-plus me-1'} />
                            {__('dashboard.service_categories.add_category')}
                        </Link>
                    ) : null
                }
                actionsVariant="card-tools"
                bodyPadding={false}
            >
                <div className="p-3 border-bottom">
                    <TableToolbar<CategoryRow>
                        rows={categories.data}
                        columns={tableColumns}
                        showSearch={false}
                        exportFilename="platform-service-categories"
                        persistKey="platform.service_categories.index"
                        onVisibleColumnIdsChange={(ids) => setVisibleColumnIds(ids)}
                        showPerPage
                        perPage={perPage}
                        perPageOptions={[10, 25, 50, 100]}
                        onPerPageChange={(value) => {
                            const next = Number(value) || 10;
                            setPerPage(next);
                            setIsFiltering(true);
                            router.get(
                                route('platform.service-categories.index'),
                                {
                                    search: search || undefined,
                                    status: status === 'all' ? undefined : status,
                                    per_page: next,
                                },
                                {
                                    preserveState: true,
                                    preserveScroll: true,
                                    replace: true,
                                    onFinish: () => setIsFiltering(false),
                                },
                            );
                        }}
                    />
                </div>

                <DataTable<CategoryRow>
                    columns={visibleTableColumns}
                    rows={categories.data}
                    rowKey={(r) => r.id}
                    emptyTitle={__('dashboard.service_categories.empty')}
                />
                <div className="px-3 pb-3">
                    <Pagination
                        links={categories.links}
                        data={{
                            current_page: categories.current_page,
                            last_page: categories.last_page,
                            prev_page_url: categories.prev_page_url,
                            next_page_url: categories.next_page_url,
                            from: categories.from,
                            to: categories.to,
                            total: categories.total,
                        }}
                    />
                </div>
            </TableCard>
        </PlatformAdminLayout>
    );
}
