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
import type { CSSProperties, FormEvent, ReactNode } from 'react';
import { useMemo, useState } from 'react';

type Row = {
    ulid: string;
    type: string;
    subject: string;
    status: string;
    priority: string;
    company: { id: number; name: string } | null;
    customer?: { user?: { name: string } | null } | null;
    order?: { id: number; order_number: string } | null;
    assigned_to?: { id: number; name: string } | null;
};

type LinkItem = { url: string | null; label: string; active: boolean };
type Paginated<T> = {
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

type TableColumn = TableToolbarColumn<Row> & {
    key: string;
    header: ReactNode;
    className?: string;
    headerClassName?: string;
};

const statusValues = ['open', 'in_progress', 'resolved', 'closed'];
const typeValues = ['complaint', 'suggestion'];

export default function PlatformFeedbackIndex({
    tickets,
    filters,
}: {
    tickets: Paginated<Row>;
    filters: { search: string; status: string; type: string; per_page?: number };
}) {
    const { direction, enumLabels } = usePage<PageProps>().props;
    const isRtl = direction === 'rtl';
    const statusLabels = enumLabels?.feedback_ticket_status ?? {};
    const typeLabels = enumLabels?.feedback_ticket_type ?? {};
    const priorityLabels = enumLabels?.feedback_priority ?? {};

    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');
    const [type, setType] = useState(filters.type ?? 'all');
    const [perPage, setPerPage] = useState(Number(filters.per_page ?? 15) || 15);
    const [isFiltering, setIsFiltering] = useState(false);
    const [visibleColumnIds, setVisibleColumnIds] = useState<string[]>([]);

    const hasActiveFilters = Boolean(
        (filters.search ?? '').trim() || (filters.status ?? 'all') !== 'all' || (filters.type ?? 'all') !== 'all',
    );

    const statusVariant = (value: string) => {
        if (value === 'resolved') return 'success' as const;
        if (value === 'in_progress') return 'info' as const;
        if (value === 'closed') return 'secondary' as const;
        return 'warning' as const;
    };

    const priorityVariant = (value: string) => {
        if (value === 'urgent') return 'danger' as const;
        if (value === 'high') return 'warning' as const;
        if (value === 'low') return 'secondary' as const;
        return 'info' as const;
    };

    const applyFilters = (event?: FormEvent) => {
        event?.preventDefault();
        setIsFiltering(true);
        router.get(
            route('platform.feedback-tickets.index'),
            {
                search: search || undefined,
                status: status === 'all' ? undefined : status,
                type: type === 'all' ? undefined : type,
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
        setType('all');
        setPerPage(15);
        setIsFiltering(true);
        router.get(route('platform.feedback-tickets.index'), {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => setIsFiltering(false),
        });
    };

    const tableColumns: TableColumn[] = useMemo(
        () => [
            {
                id: 'subject',
                key: 'subject',
                label: __('dashboard.feedback_tickets.col_subject'),
                header: __('dashboard.feedback_tickets.col_subject'),
                render: (row) => <strong>{row.subject}</strong>,
                searchText: (row) => row.subject,
            },
            {
                id: 'type',
                key: 'type',
                label: __('dashboard.feedback_tickets.col_type'),
                header: __('dashboard.feedback_tickets.col_type'),
                render: (row) => <StatusBadge variant="secondary" pill>{typeLabels[row.type] ?? row.type}</StatusBadge>,
                searchText: (row) => typeLabels[row.type] ?? row.type,
            },
            {
                id: 'status',
                key: 'status',
                label: __('dashboard.feedback_tickets.col_status'),
                header: __('dashboard.feedback_tickets.col_status'),
                render: (row) => <StatusBadge variant={statusVariant(row.status)} pill>{statusLabels[row.status] ?? row.status}</StatusBadge>,
                searchText: (row) => statusLabels[row.status] ?? row.status,
            },
            {
                id: 'priority',
                key: 'priority',
                label: __('dashboard.feedback_tickets.col_priority'),
                header: __('dashboard.feedback_tickets.col_priority'),
                render: (row) => <StatusBadge variant={priorityVariant(row.priority)} pill>{priorityLabels[row.priority] ?? row.priority}</StatusBadge>,
                searchText: (row) => priorityLabels[row.priority] ?? row.priority,
            },
            {
                id: 'company',
                key: 'company',
                label: __('dashboard.feedback_tickets.col_company'),
                header: __('dashboard.feedback_tickets.col_company'),
                render: (row) => row.company?.name ?? <span className="text-muted">—</span>,
                searchText: (row) => row.company?.name ?? '',
            },
            {
                id: 'customer',
                key: 'customer',
                label: __('dashboard.feedback_tickets.col_customer'),
                header: __('dashboard.feedback_tickets.col_customer'),
                render: (row) => row.customer?.user?.name ?? <span className="text-muted">—</span>,
                searchText: (row) => row.customer?.user?.name ?? '',
            },
            {
                id: 'order',
                key: 'order',
                label: __('dashboard.feedback_tickets.col_order'),
                header: __('dashboard.feedback_tickets.col_order'),
                render: (row) => row.order?.order_number ? <span dir="ltr">{row.order.order_number}</span> : <span className="text-muted">—</span>,
                searchText: (row) => row.order?.order_number ?? '',
            },
            {
                id: 'assigned_to',
                key: 'assigned_to',
                label: __('dashboard.feedback_tickets.col_assigned_to'),
                header: __('dashboard.feedback_tickets.col_assigned_to'),
                render: (row) => row.assigned_to?.name ?? <span className="text-muted">{__('dashboard.feedback_tickets.not_assigned')}</span>,
                searchText: (row) => row.assigned_to?.name ?? '',
            },
            {
                id: 'actions',
                key: 'actions',
                label: __('dashboard.feedback_tickets.col_actions'),
                header: <span className="d-block text-end">{__('dashboard.feedback_tickets.col_actions')}</span>,
                className: 'text-end',
                render: (row) => (
                    <Link href={route('platform.feedback-tickets.show', row.ulid)} className="btn btn-outline-primary btn-sm">
                        <i className={isRtl ? 'fas fa-folder-open ml-1' : 'fas fa-folder-open mr-1'} />
                        {__('dashboard.feedback_tickets.open_ticket')}
                    </Link>
                ),
            },
        ],
        [isRtl, priorityLabels, statusLabels, typeLabels],
    );

    const resolvedVisibleColumnIds = visibleColumnIds.length ? visibleColumnIds : tableColumns.map((column) => column.id);
    const visibleTableColumns = tableColumns.filter((column) => resolvedVisibleColumnIds.includes(column.id));

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.platform_feedback"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('dashboard.feedback_tickets.page_title')}</h4>}
        >
            <Head title={__('dashboard.feedback_tickets.page_title')} />

            <div className="mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <p className="text-muted mb-0">{__('dashboard.feedback_tickets.page_subtitle')}</p>
                <Link href={route('platform.dashboard')} className="btn btn-outline-secondary btn-sm">
                    <i className={isRtl ? 'fas fa-arrow-right ml-1' : 'fas fa-arrow-left mr-1'} />
                    {__('dashboard.feedback_tickets.overview')}
                </Link>
            </div>

            <div className={hasActiveFilters ? 'card card-outline card-primary' : 'card card-outline card-primary collapsed-card'}>
                <div className="card-header" data-card-widget="collapse" style={{ cursor: 'pointer' }}>
                    <h3 className="card-title">
                        <i className={isRtl ? 'fas fa-filter ml-1' : 'fas fa-filter mr-1'} />
                        {__('dashboard.feedback_tickets.filters_title')}
                    </h3>
                    <div className="card-tools">
                        <button type="button" className="btn btn-tool">
                            <i className="fas fa-plus" />
                        </button>
                    </div>
                </div>
                <div className="card-body" style={hasActiveFilters ? undefined : ({ display: 'none' } as CSSProperties)}>
                    <form className="row" onSubmit={applyFilters}>
                        <div className="col-sm-12 col-md-6 col-lg-6 mb-3">
                            <input
                                className="form-control"
                                name="search"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder={__('dashboard.feedback_tickets.search_placeholder')}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            />
                        </div>
                        <div className="col-sm-6 col-md-3 col-lg-3 mb-3">
                            <select
                                className="custom-select"
                                name="status"
                                value={status}
                                onChange={(event) => setStatus(event.target.value)}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            >
                                <option value="all">{__('dashboard.feedback_tickets.all_statuses')}</option>
                                {statusValues.map((value) => (
                                    <option key={value} value={value}>
                                        {statusLabels[value] ?? value}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="col-sm-6 col-md-3 col-lg-3 mb-3">
                            <select
                                className="custom-select"
                                name="type"
                                value={type}
                                onChange={(event) => setType(event.target.value)}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            >
                                <option value="all">{__('dashboard.feedback_tickets.all_types')}</option>
                                {typeValues.map((value) => (
                                    <option key={value} value={value}>
                                        {typeLabels[value] ?? value}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="col-sm-12" style={{ textAlign: isRtl ? 'right' : 'left' }}>
                            <button type="submit" className="btn btn-primary px-4" disabled={isFiltering}>
                                <i className={isRtl ? 'fas fa-filter ml-1' : 'fas fa-filter mr-1'} />
                                {__('dashboard.actions.filter')}
                            </button>
                            <button type="button" className="btn btn-danger px-4 mx-2" onClick={resetFilters} disabled={isFiltering}>
                                <i className={isRtl ? 'fas fa-undo ml-1' : 'fas fa-undo mr-1'} />
                                {__('dashboard.actions.reset')}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <TableCard title={__('dashboard.feedback_tickets.list_title')} actionsVariant="card-tools" bodyPadding={false}>
                <div className="p-3 border-bottom">
                    <TableToolbar<Row>
                        rows={tickets.data}
                        columns={tableColumns}
                        showSearch={false}
                        exportFilename="platform-feedback-tickets"
                        persistKey="platform.feedback_tickets.index"
                        onVisibleColumnIdsChange={(ids) => setVisibleColumnIds(ids)}
                        showPerPage
                        perPage={perPage}
                        perPageOptions={[10, 15, 25, 50, 100]}
                        onPerPageChange={(value) => {
                            const next = Number(value) || 15;
                            setPerPage(next);
                            setIsFiltering(true);
                            router.get(
                                route('platform.feedback-tickets.index'),
                                {
                                    search: search || undefined,
                                    status: status === 'all' ? undefined : status,
                                    type: type === 'all' ? undefined : type,
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
                <DataTable<Row>
                    columns={visibleTableColumns}
                    rows={tickets.data}
                    rowKey={(row) => row.ulid}
                    emptyTitle={__('dashboard.feedback_tickets.empty')}
                />
                <div className="px-3 pb-3">
                    <Pagination links={tickets.links} data={tickets} />
                </div>
            </TableCard>
        </PlatformAdminLayout>
    );
}
