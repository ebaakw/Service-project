import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import DataTable from '@/Components/ui/DataTable';
import Pagination from '@/Components/ui/Pagination';
import StatusBadge from '@/Components/ui/StatusBadge';
import TableCard from '@/Components/ui/TableCard';
import type { TableToolbarColumn } from '@/Components/ui/TableToolbar';
import TableToolbar from '@/Components/ui/TableToolbar';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

type CompanyRow = { id: number; name: string };

type ServiceCategory = { id: number; name_ar: string; name_en: string | null };

type TechnicianRow = {
    id: number;
    is_active: boolean;
    employee_code: string | null;
    availability_status: string | null;
    availability_status_label?: string | null;
    review_status?: string | null;
    reviewed_at?: string | null;
    rejection_reason?: string | null;
    created_at?: string | null;
    company: CompanyRow;
    user: { name: string; email: string | null; phone: string | null; avatar_url: string | null };
    service_categories: ServiceCategory[];
    can_update?: boolean;
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

export default function PlatformTechniciansIndex({
    technicians,
    filters,
}: {
    technicians: Paginator<TechnicianRow>;
    filters: { search: string; review_status?: string; per_page?: number };
}) {
    const { direction } = usePage<PageProps>().props;
    const isRtl = direction === 'rtl';
    const { enumLabels } = usePage<PageProps>().props;
    const statusLabels = enumLabels?.technician_review_status ?? {};

    const [search, setSearch] = useState(filters.search ?? '');
    const [reviewStatus, setReviewStatus] = useState<string>(filters.review_status ?? 'all');
    const [perPage, setPerPage] = useState<number>(Number(filters.per_page ?? 10) || 10);
    const [isFiltering, setIsFiltering] = useState(false);
    const [visibleColumnIds, setVisibleColumnIds] = useState<string[]>([]);
    const hasActiveFilters = Boolean((filters.search ?? '').trim() || (filters.review_status ?? 'all') !== 'all');

    const applyFilters = () => {
        setIsFiltering(true);
        router.get(
            route('platform.technicians.index'),
            {
                search: search || undefined,
                review_status: reviewStatus && reviewStatus !== 'all' ? reviewStatus : undefined,
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
        setReviewStatus('all');
        setPerPage(10);
        setIsFiltering(true);
        router.get(route('platform.technicians.index'), {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => setIsFiltering(false),
        });
    };

    const tableColumns: Array<
        TableToolbarColumn<TechnicianRow> & { key: string; header: React.ReactNode; className?: string; headerClassName?: string }
    > = useMemo(
        () => [
            {
                id: 'avatar',
                key: 'avatar',
                label: __('tenant.technicians.avatar'),
                header: __('tenant.technicians.avatar'),
                render: (r) =>
                    r.user.avatar_url ? (
                        <img
                            src={r.user.avatar_url}
                            alt=""
                            style={{ width: 36, height: 36, objectFit: 'cover' }}
                            className="rounded border bg-white"
                        />
                    ) : (
                        <span className="text-muted">—</span>
                    ),
            },
            {
                id: 'name',
                key: 'name',
                label: __('tenant.technicians.name'),
                header: __('tenant.technicians.name'),
                render: (r) => <strong>{r.user.name}</strong>,
                searchText: (r) => r.user.name ?? '',
            },
            {
                id: 'company',
                key: 'company',
                label: __('tenant.technicians.company'),
                header: __('tenant.technicians.company'),
                render: (r) => r.company.name,
                searchText: (r) => r.company.name ?? '',
            },
            {
                id: 'contact',
                key: 'contact',
                label: __('tenant.technicians.contact'),
                header: __('tenant.technicians.contact'),
                render: (r) => (
                    <div dir="ltr">
                        <div>{r.user.email || '—'}</div>
                        <div className="text-muted">{r.user.phone || '—'}</div>
                    </div>
                ),
                searchText: (r) => `${r.user.email ?? ''} ${r.user.phone ?? ''}`.trim(),
            },
            {
                id: 'categories',
                key: 'categories',
                label: __('tenant.technicians.categories'),
                header: __('tenant.technicians.categories'),
                render: (r) => r.service_categories.map((c) => c.name_ar).join(', ') || '—',
                searchText: (r) => r.service_categories.map((c) => `${c.name_ar ?? ''} ${c.name_en ?? ''}`.trim()).join(' '),
            },
            {
                id: 'review_status',
                key: 'review_status',
                label: __('tenant.technicians.review_status'),
                header: __('tenant.technicians.review_status'),
                render: (r) => {
                    const s = (r.review_status ?? '').toLowerCase();
                    if (s === 'approved') return <StatusBadge variant="success">{statusLabels.approved ?? 'Approved'}</StatusBadge>;
                    if (s === 'pending') return <StatusBadge variant="warning">{statusLabels.pending ?? 'Pending'}</StatusBadge>;
                    if (s === 'rejected')
                        return (
                            <div>
                                <StatusBadge variant="danger">{statusLabels.rejected ?? 'Rejected'}</StatusBadge>
                                {r.rejection_reason ? <div className="text-muted small mt-1">{r.rejection_reason}</div> : null}
                            </div>
                        );
                    return <span className="text-muted">—</span>;
                },
            },
            {
                id: 'active',
                key: 'active',
                label: __('tenant.technicians.active'),
                header: __('tenant.technicians.active'),
                render: (r) =>
                    r.is_active ? (
                        <StatusBadge variant="success">{__('tenant.yes')}</StatusBadge>
                    ) : (
                        <StatusBadge variant="secondary">{__('tenant.no')}</StatusBadge>
                    ),
                searchText: (r) => (r.is_active ? __('tenant.yes') : __('tenant.no')),
            },
            {
                id: 'created_at',
                key: 'created_at',
                label: __('tenant.technicians.created_at'),
                header: __('tenant.technicians.created_at'),
                render: (r) => (r.created_at ? new Date(r.created_at).toLocaleString() : '—'),
            },
            {
                id: 'actions',
                key: 'actions',
                label: __('tenant.technicians.actions'),
                header: <span className="d-block text-end">{__('tenant.technicians.actions')}</span>,
                className: 'text-end',
                render: (r) => (
                    <div className="btn-group btn-group-sm">
                        <Link href={route('platform.technicians.show', r.id)} className="btn btn-outline-primary">
                            {__('tenant.technicians.view')}
                        </Link>
                        {r.can_update ? (
                            <Link href={route('platform.technicians.edit', r.id)} className="btn btn-outline-secondary">
                                {__('tenant.technicians.edit')}
                            </Link>
                        ) : null}
                    </div>
                ),
            },
        ],
        [],
    );

    const resolvedVisibleColumnIds = visibleColumnIds.length > 0 ? visibleColumnIds : tableColumns.map((c) => c.id);
    const visibleTableColumns = tableColumns.filter((c) => resolvedVisibleColumnIds.includes(c.id));

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.all_technicians"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('tenant.technicians.title')}</h4>}
        >
            <Head title={__('tenant.technicians.title')} />

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
                <div className="card-body" style={hasActiveFilters ? undefined : ({ display: 'none' } as any)}>
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
                                placeholder={__('tenant.technicians.searchPlaceholder')}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            />
                        </div>

                        <div className="col-sm-12 col-md-3 col-lg-3 mt-3 mt-md-0">
                            <select className="form-control" value={reviewStatus} onChange={(e) => setReviewStatus(e.target.value)}>
                                <option value="all">{__('tenant.all')}</option>
                                <option value="pending">{statusLabels.pending ?? 'Pending'}</option>
                                <option value="approved">{statusLabels.approved ?? 'Approved'}</option>
                                <option value="rejected">{statusLabels.rejected ?? 'Rejected'}</option>
                            </select>
                        </div>

                        <div className="col-sm-12 col-md-3 col-lg-3 mt-3 mt-md-0">
                            <select className="form-control" value={perPage} onChange={(e) => setPerPage(Number(e.target.value) || 10)}>
                                {[10, 25, 50, 100].map((n) => (
                                    <option key={n} value={n}>
                                        {n}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="col-sm-12 mt-3" style={{ textAlign: isRtl ? 'right' : 'left' }}>
                            <button type="submit" className="btn btn-primary px-4" disabled={isFiltering}>
                                <i className={isRtl ? 'fas fa-filter ms-1' : 'fas fa-filter me-1'} />
                                {__('tenant.filter')}
                            </button>
                            <button type="button" className="btn btn-danger px-4 mx-2" onClick={resetFilters} disabled={isFiltering}>
                                <i className={isRtl ? 'fas fa-undo ms-1' : 'fas fa-undo me-1'} />
                                {__('tenant.reset')}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <TableCard title={__('tenant.technicians.list')} actionsVariant="card-tools" bodyPadding={false}>
                <div className="p-3 border-bottom">
                    <TableToolbar<TechnicianRow>
                        rows={technicians.data}
                        columns={tableColumns}
                        showSearch={false}
                        exportFilename="platform-technicians"
                        persistKey="platform.technicians.index.v2"
                        onVisibleColumnIdsChange={(ids) => setVisibleColumnIds(ids)}
                        showPerPage
                        perPage={perPage}
                        perPageOptions={[10, 25, 50, 100]}
                        onPerPageChange={(value) => {
                            const next = Number(value) || 10;
                            setPerPage(next);
                            setIsFiltering(true);
                            router.get(
                                route('platform.technicians.index'),
                                { search: search || undefined, review_status: reviewStatus && reviewStatus !== 'all' ? reviewStatus : undefined, per_page: next },
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

                <DataTable<TechnicianRow> columns={visibleTableColumns} rows={technicians.data} rowKey={(r) => r.id} emptyTitle={__('tenant.technicians.empty')} />
                <div className="px-3 pb-3">
                    <Pagination
                        links={technicians.links}
                        data={{
                            current_page: technicians.current_page,
                            last_page: technicians.last_page,
                            prev_page_url: technicians.prev_page_url,
                            next_page_url: technicians.next_page_url,
                            from: technicians.from,
                            to: technicians.to,
                            total: technicians.total,
                        }}
                    />
                </div>
            </TableCard>
        </PlatformAdminLayout>
    );
}
