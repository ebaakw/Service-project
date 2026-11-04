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

type BannerRow = {
    id: number;
    title_ar: string;
    title_en: string | null;
    link_type: string | null;
    link_value: string | null;
    sort_order: number;
    is_active: boolean;
    image_url: string | null;
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

export default function PlatformBannersIndex({
    banners,
    filters,
    can,
}: {
    banners: Paginator<BannerRow>;
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
            route('platform.banners.index'),
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
            route('platform.banners.index'),
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
        if (!confirm(__('dashboard.banners.delete_confirm'))) {
            return;
        }
        router.delete(route('platform.banners.destroy', id), { preserveScroll: true });
    }, []);

    const tableColumns: Array<
        TableToolbarColumn<BannerRow> & { key: string; header: React.ReactNode; className?: string; headerClassName?: string }
    > = useMemo(
        () => [
            {
                id: 'image',
                key: 'image',
                label: __('dashboard.banners.col_image'),
                header: __('dashboard.banners.col_image'),
                render: (r) =>
                    r.image_url ? (
                        <img src={r.image_url} alt="" style={{ height: 40, width: 72, objectFit: 'cover' }} />
                    ) : (
                        <span>—</span>
                    ),
                searchText: () => '',
            },
            {
                id: 'title_ar',
                key: 'title_ar',
                label: __('dashboard.banners.col_title_ar'),
                header: __('dashboard.banners.col_title_ar'),
                render: (r) => <strong>{r.title_ar}</strong>,
                searchText: (r) => r.title_ar ?? '',
            },
            {
                id: 'link_type',
                key: 'link_type',
                label: __('dashboard.banners.col_link_type'),
                header: __('dashboard.banners.col_link_type'),
                render: (r) => (
                    <span dir="ltr">{r.link_type ? __(`dashboard.banners.link_types.${r.link_type}`) : '—'}</span>
                ),
                searchText: (r) => r.link_type ?? '',
            },
            {
                id: 'sort_order',
                key: 'sort_order',
                label: __('dashboard.banners.col_sort'),
                header: __('dashboard.banners.col_sort'),
                render: (r) => <span dir="ltr">{r.sort_order}</span>,
                searchText: (r) => String(r.sort_order),
            },
            {
                id: 'is_active',
                key: 'is_active',
                label: __('dashboard.banners.col_active'),
                header: __('dashboard.banners.col_active'),
                render: (r) => (
                    <StatusBadge variant={r.is_active ? 'success' : 'secondary'} pill>
                        {r.is_active ? __('dashboard.banners.yes') : __('dashboard.banners.no')}
                    </StatusBadge>
                ),
                searchText: (r) => (r.is_active ? __('dashboard.banners.yes') : __('dashboard.banners.no')),
            },
            {
                id: 'actions',
                key: 'actions',
                label: __('dashboard.banners.col_actions'),
                header: <span className="d-block text-end">{__('dashboard.banners.col_actions')}</span>,
                className: 'text-end',
                render: (r) => (
                    <div className="btn-group">
                        {r.can_update ? (
                            <Link href={route('platform.banners.edit', r.id)} className="btn btn-outline-primary btn-sm">
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
            breadcrumbKey="dashboard.nav.banners"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('dashboard.banners.page_title')}</h4>}
        >
            <Head title={__('dashboard.banners.page_title')} />

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
                                placeholder={__('dashboard.banners.search_placeholder')}
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
                                <option value="all">{__('dashboard.banners.status_all')}</option>
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
                title={__('dashboard.banners.list_title')}
                actions={
                    can?.create ? (
                        <Link href={route('platform.banners.create')} className="btn btn-success btn-sm">
                            <i className={isRtl ? 'fas fa-plus ms-1' : 'fas fa-plus me-1'} />
                            {__('dashboard.banners.add_banner')}
                        </Link>
                    ) : null
                }
                actionsVariant="card-tools"
                bodyPadding={false}
            >
                <div className="p-3 border-bottom">
                    <TableToolbar<BannerRow>
                        rows={banners.data}
                        columns={tableColumns}
                        showSearch={false}
                        exportFilename="platform-banners"
                        persistKey="platform.banners.index"
                        onVisibleColumnIdsChange={(ids) => setVisibleColumnIds(ids)}
                        showPerPage
                        perPage={perPage}
                        perPageOptions={[10, 25, 50, 100]}
                        onPerPageChange={(value) => {
                            const next = Number(value) || 10;
                            setPerPage(next);
                            setIsFiltering(true);
                            router.get(
                                route('platform.banners.index'),
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

                <DataTable<BannerRow>
                    columns={visibleTableColumns}
                    rows={banners.data}
                    rowKey={(r) => r.id}
                    emptyTitle={__('dashboard.banners.empty')}
                />
                <div className="px-3 pb-3">
                    <Pagination
                        links={banners.links}
                        data={{
                            current_page: banners.current_page,
                            last_page: banners.last_page,
                            prev_page_url: banners.prev_page_url,
                            next_page_url: banners.next_page_url,
                            from: banners.from,
                            to: banners.to,
                            total: banners.total,
                        }}
                    />
                </div>
            </TableCard>
        </PlatformAdminLayout>
    );
}
