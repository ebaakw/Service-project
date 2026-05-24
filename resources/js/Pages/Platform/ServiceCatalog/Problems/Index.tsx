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

type Category = { id: number; name_ar: string; slug: string };
type ProblemRow = {
    id: number;
    title_ar: string;
    title_en: string | null;
    is_other: boolean;
    is_active: boolean;
    sort_order: number;
};
type Paginator<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page?: number;
    last_page?: number;
    prev_page_url?: string | null;
    next_page_url?: string | null;
    from?: number | null;
    to?: number | null;
    total?: number | null;
};

export default function PlatformServiceProblemsIndex({
    category,
    problems,
    filters,
}: {
    category: Category;
    problems: Paginator<ProblemRow>;
    filters: { search: string; status: string; per_page?: number };
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
            route('platform.service-categories.problems.index', category.id),
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
            route('platform.service-categories.problems.index', category.id),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => setIsFiltering(false),
            },
        );
    };

    const destroy = useCallback(
        (id: number) => {
            if (!confirm(__('dashboard.service_problems.delete_confirm'))) {
                return;
            }

            router.delete(route('platform.service-categories.problems.destroy', [category.id, id]), { preserveScroll: true });
        },
        [category.id],
    );

    const tableColumns: Array<
        TableToolbarColumn<ProblemRow> & { key: string; header: React.ReactNode; className?: string; headerClassName?: string }
    > = useMemo(
        () => [
            {
                id: 'title_ar',
                key: 'title_ar',
                label: __('dashboard.service_problems.col_title_ar'),
                header: __('dashboard.service_problems.col_title_ar'),
                render: (row) => <strong>{row.title_ar}</strong>,
                searchText: (row) => row.title_ar ?? '',
            },
            {
                id: 'title_en',
                key: 'title_en',
                label: __('dashboard.service_problems.col_title_en'),
                header: __('dashboard.service_problems.col_title_en'),
                render: (row) => <span>{row.title_en ?? '—'}</span>,
                searchText: (row) => row.title_en ?? '',
            },
            {
                id: 'is_other',
                key: 'is_other',
                label: __('dashboard.service_problems.col_other'),
                header: __('dashboard.service_problems.col_other'),
                render: (row) => (
                    <StatusBadge variant={row.is_other ? 'info' : 'secondary'} pill>
                        {row.is_other ? __('dashboard.service_categories.yes') : __('dashboard.service_categories.no')}
                    </StatusBadge>
                ),
                searchText: (row) =>
                    row.is_other ? __('dashboard.service_categories.yes') : __('dashboard.service_categories.no'),
            },
            {
                id: 'sort_order',
                key: 'sort_order',
                label: __('dashboard.service_problems.col_sort'),
                header: __('dashboard.service_problems.col_sort'),
                render: (row) => <span dir="ltr">{row.sort_order}</span>,
                searchText: (row) => String(row.sort_order),
            },
            {
                id: 'is_active',
                key: 'is_active',
                label: __('dashboard.service_problems.col_active'),
                header: __('dashboard.service_problems.col_active'),
                render: (row) => (
                    <StatusBadge variant={row.is_active ? 'success' : 'secondary'} pill>
                        {row.is_active ? __('dashboard.status.active') : __('dashboard.status.inactive')}
                    </StatusBadge>
                ),
                searchText: (row) => (row.is_active ? __('dashboard.status.active') : __('dashboard.status.inactive')),
            },
            {
                id: 'actions',
                key: 'actions',
                label: __('dashboard.service_problems.col_actions'),
                header: <span className="d-block text-end">{__('dashboard.service_problems.col_actions')}</span>,
                className: 'text-end',
                render: (row) => (
                    <div className="btn-group">
                        <Link
                            href={route('platform.service-categories.problems.edit', [category.id, row.id])}
                            className="btn btn-outline-primary btn-sm"
                        >
                            {__('dashboard.actions.edit')}
                        </Link>
                        <button type="button" className="btn btn-outline-danger btn-sm" onClick={() => destroy(row.id)}>
                            {__('dashboard.actions.delete')}
                        </button>
                    </div>
                ),
            },
        ],
        [category.id, destroy],
    );

    const resolvedVisibleColumnIds = visibleColumnIds.length > 0 ? visibleColumnIds : tableColumns.map((c) => c.id);
    const visibleTableColumns = tableColumns.filter((c) => resolvedVisibleColumnIds.includes(c.id));

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.global_service_catalog"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('dashboard.service_problems.page_title')}</h4>}
        >
            <Head title={__('dashboard.service_problems.page_title')} />

            <div className="mb-3">
                <Link href={route('platform.service-categories.index')} className="btn btn-outline-secondary btn-sm">
                    <i className={isRtl ? 'fas fa-arrow-right ml-1' : 'fas fa-arrow-left mr-1'} />
                    {__('dashboard.actions.back')}
                </Link>
            </div>

            <div className="card card-outline card-info">
                <div className="card-body py-2">
                    <span className="text-muted">{__('dashboard.service_problems.category_label')}</span>
                    <strong className="mx-1">{category.name_ar}</strong>
                    <span dir="ltr" className="text-muted">
                        ({category.slug})
                    </span>
                </div>
            </div>

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
                                placeholder={__('dashboard.service_problems.search_placeholder')}
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
                                <i className={isRtl ? 'fas fa-filter ml-1' : 'fas fa-filter mr-1'} />
                                {__('tenant.filter')}
                            </button>
                            <button
                                type="button"
                                className="btn btn-danger px-4 mx-2"
                                onClick={resetFilters}
                                disabled={isFiltering}
                            >
                                <i className={isRtl ? 'fas fa-undo ml-1' : 'fas fa-undo mr-1'} />
                                {__('tenant.reset')}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <TableCard
                title={__('dashboard.service_problems.list_title')}
                actions={
                    <Link href={route('platform.service-categories.problems.create', category.id)} className="btn btn-success btn-sm">
                        <i className={isRtl ? 'fas fa-plus ml-1' : 'fas fa-plus mr-1'} />
                        {__('dashboard.service_problems.add_problem')}
                    </Link>
                }
                actionsVariant="card-tools"
                bodyPadding={false}
            >
                <div className="p-3 border-bottom">
                    <TableToolbar<ProblemRow>
                        rows={problems.data}
                        columns={tableColumns}
                        showSearch={false}
                        exportFilename="platform-service-problems"
                        persistKey="platform.service_problems.index"
                        onVisibleColumnIdsChange={(ids) => setVisibleColumnIds(ids)}
                        showPerPage
                        perPage={perPage}
                        perPageOptions={[10, 25, 50, 100]}
                        onPerPageChange={(value) => {
                            const next = Number(value) || 10;
                            setPerPage(next);
                            setIsFiltering(true);
                            router.get(
                                route('platform.service-categories.problems.index', category.id),
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

                <DataTable<ProblemRow>
                    columns={visibleTableColumns}
                    rows={problems.data}
                    rowKey={(row) => row.id}
                    emptyTitle={__('dashboard.service_problems.empty')}
                />
                <div className="px-3 pb-3">
                    <Pagination
                        links={problems.links}
                        data={{
                            current_page: problems.current_page,
                            last_page: problems.last_page,
                            prev_page_url: problems.prev_page_url,
                            next_page_url: problems.next_page_url,
                            from: problems.from,
                            to: problems.to,
                            total: problems.total,
                        }}
                    />
                </div>
            </TableCard>
        </PlatformAdminLayout>
    );
}
