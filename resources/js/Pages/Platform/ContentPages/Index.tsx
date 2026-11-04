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

type PageRow = {
    id: number;
    slug: string;
    title_ar: string;
    title_en: string | null;
    is_active: boolean;
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

export default function PlatformContentPagesIndex({
    pages,
    filters,
    can,
}: {
    pages: Paginator<PageRow>;
    filters: { search: string; per_page?: number };
    can: { create?: boolean };
}) {
    const { direction } = usePage<PageProps>().props;
    const isRtl = direction === 'rtl';

    const [search, setSearch] = useState(filters.search ?? '');
    const [perPage, setPerPage] = useState<number>(Number(filters.per_page ?? 10) || 10);
    const [isFiltering, setIsFiltering] = useState(false);
    const [visibleColumnIds, setVisibleColumnIds] = useState<string[]>([]);

    const hasActiveFilters = Boolean((filters.search ?? '').trim());

    const applyFilters = () => {
        setIsFiltering(true);
        router.get(
            route('platform.content-pages.index'),
            {
                search: search || undefined,
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
        setPerPage(10);
        setIsFiltering(true);
        router.get(
            route('platform.content-pages.index'),
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
        if (!confirm(__('dashboard.content_pages.delete_confirm'))) {
            return;
        }
        router.delete(route('platform.content-pages.destroy', id), { preserveScroll: true });
    }, []);

    const tableColumns: Array<
        TableToolbarColumn<PageRow> & { key: string; header: React.ReactNode; className?: string; headerClassName?: string }
    > = useMemo(
        () => [
            {
                id: 'slug',
                key: 'slug',
                label: __('dashboard.content_pages.col_slug'),
                header: __('dashboard.content_pages.col_slug'),
                render: (r) => (
                    <span dir="ltr" className="d-inline-block">
                        {r.slug}
                    </span>
                ),
                searchText: (r) => r.slug ?? '',
            },
            {
                id: 'title_ar',
                key: 'title_ar',
                label: __('dashboard.content_pages.col_title_ar'),
                header: __('dashboard.content_pages.col_title_ar'),
                render: (r) => <strong>{r.title_ar}</strong>,
                searchText: (r) => r.title_ar ?? '',
            },
            {
                id: 'is_active',
                key: 'is_active',
                label: __('dashboard.content_pages.col_active'),
                header: __('dashboard.content_pages.col_active'),
                render: (r) => (
                    <StatusBadge variant={r.is_active ? 'success' : 'secondary'} pill>
                        {r.is_active ? __('dashboard.content_pages.yes') : __('dashboard.content_pages.no')}
                    </StatusBadge>
                ),
                searchText: (r) =>
                    r.is_active ? __('dashboard.content_pages.yes') : __('dashboard.content_pages.no'),
            },
            {
                id: 'actions',
                key: 'actions',
                label: __('dashboard.content_pages.col_actions'),
                header: <span className="d-block text-end">{__('dashboard.content_pages.col_actions')}</span>,
                className: 'text-end',
                render: (r) => (
                    <div className="btn-group">
                        {r.can_update ? (
                            <Link
                                href={route('platform.content-pages.edit', r.id)}
                                className="btn btn-outline-primary btn-sm"
                            >
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
        [destroy],
    );

    const resolvedVisibleColumnIds = visibleColumnIds.length > 0 ? visibleColumnIds : tableColumns.map((c) => c.id);
    const visibleTableColumns = tableColumns.filter((c) => resolvedVisibleColumnIds.includes(c.id));

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.content_pages"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('dashboard.content_pages.page_title')}</h4>}
        >
            <Head title={__('dashboard.content_pages.page_title')} />

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
                        <div className="col-sm-12 col-md-6">
                            <input
                                className="form-control"
                                name="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder={__('dashboard.content_pages.search_placeholder')}
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
                title={__('dashboard.content_pages.list_title')}
                actions={
                    can?.create ? (
                        <Link href={route('platform.content-pages.create')} className="btn btn-success btn-sm">
                            <i className={isRtl ? 'fas fa-plus ms-1' : 'fas fa-plus me-1'} />
                            {__('dashboard.content_pages.add_page')}
                        </Link>
                    ) : null
                }
                actionsVariant="card-tools"
                bodyPadding={false}
            >
                <div className="p-3 border-bottom">
                    <TableToolbar<PageRow>
                        rows={pages.data}
                        columns={tableColumns}
                        showSearch={false}
                        exportFilename="platform-content-pages"
                        persistKey="platform.content_pages.index"
                        onVisibleColumnIdsChange={(ids) => setVisibleColumnIds(ids)}
                        showPerPage
                        perPage={perPage}
                        perPageOptions={[10, 25, 50, 100]}
                        onPerPageChange={(value) => {
                            const next = Number(value) || 10;
                            setPerPage(next);
                            setIsFiltering(true);
                            router.get(
                                route('platform.content-pages.index'),
                                {
                                    search: search || undefined,
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

                <DataTable<PageRow>
                    columns={visibleTableColumns}
                    rows={pages.data}
                    rowKey={(r) => r.id}
                    emptyTitle={__('dashboard.content_pages.empty')}
                />
                <div className="px-3 pb-3">
                    <Pagination
                        links={pages.links}
                        data={{
                            current_page: pages.current_page,
                            last_page: pages.last_page,
                            prev_page_url: pages.prev_page_url,
                            next_page_url: pages.next_page_url,
                            from: pages.from,
                            to: pages.to,
                            total: pages.total,
                        }}
                    />
                </div>
            </TableCard>
        </PlatformAdminLayout>
    );
}
