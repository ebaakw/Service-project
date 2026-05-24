import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import DataTable from '@/Components/ui/DataTable';
import Pagination from '@/Components/ui/Pagination';
import StatusBadge from '@/Components/ui/StatusBadge';
import TableCard from '@/Components/ui/TableCard';
import type { TableToolbarColumn } from '@/Components/ui/TableToolbar';
import TableToolbar from '@/Components/ui/TableToolbar';
import type { PageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useMemo, useState } from 'react';

type Row = {
    id: number;
    invoice_number: string;
    status: string;
    total_amount: string;
    created_at: string | null;
    company: { name: string; name_ar?: string | null; name_en?: string | null } | null;
    customer: { user: { name: string; name_ar?: string | null; name_en?: string | null; email?: string | null; phone?: string | null } } | null;
    order: { order_number: string } | null;
};

type Paginated<T> = {
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

type Filters = {
    search: string;
    status: string;
    date_from: string;
    date_to: string;
    per_page?: number;
};

function localizedName(
    locale: string,
    row: { name: string; name_ar?: string | null; name_en?: string | null } | null | undefined,
): string {
    if (!row) {
        return '—';
    }
    if (locale === 'en') {
        return row.name_en || row.name_ar || row.name;
    }
    return row.name_ar || row.name_en || row.name;
}

function formatDate(value: string | null, locale: string): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat(locale === 'ar' ? 'ar' : 'en', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(date);
}

function invoiceStatusVariant(status: string) {
    if (status === 'paid') return 'success' as const;
    if (status === 'sent') return 'info' as const;
    if (status === 'draft') return 'secondary' as const;
    if (status === 'cancelled' || status === 'refunded') return 'danger' as const;
    return 'secondary' as const;
}

export default function PlatformInvoicesIndex({
    invoices,
    filters,
}: {
    invoices: Paginated<Row>;
    filters: Filters;
}) {
    const page = usePage<PageProps>().props;
    const t = page.t?.invoices ?? {};
    const actions = page.t?.actions ?? {};
    const locale = page.locale;
    const isRtl = page.direction === 'rtl';
    const statusLabels = page.enumLabels?.invoice_status ?? {};
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');
    const [perPage, setPerPage] = useState<number>(Number(filters.per_page ?? 10) || 10);
    const [isFiltering, setIsFiltering] = useState(false);
    const [visibleColumnIds, setVisibleColumnIds] = useState<string[]>([]);
    const hasActiveFilters = Boolean(
        (filters.search ?? '').trim() || (filters.status ?? '').trim() || (filters.date_from ?? '').trim() || (filters.date_to ?? '').trim(),
    );

    const applyFilters = (nextPerPage = perPage) => {
        setIsFiltering(true);
        router.get(
            route('platform.invoices.index'),
            {
                search: search || undefined,
                status: status || undefined,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
                per_page: nextPerPage || undefined,
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
        setStatus('');
        setDateFrom('');
        setDateTo('');
        setPerPage(10);
        setIsFiltering(true);
        router.get(
            route('platform.invoices.index'),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => setIsFiltering(false),
            },
        );
    };

    const tableColumns: Array<
        TableToolbarColumn<Row> & {
            key: string;
            header: ReactNode;
            className?: string;
            headerClassName?: string;
        }
    > = useMemo(
        () => [
            {
                id: 'invoice',
                key: 'invoice',
                label: t.col_invoice ?? 'Invoice',
                header: t.col_invoice ?? 'Invoice',
                render: (r) => <span dir="ltr">{r.invoice_number}</span>,
                searchText: (r) => r.invoice_number ?? '',
            },
            {
                id: 'order',
                key: 'order',
                label: t.col_order ?? 'Order',
                header: t.col_order ?? 'Order',
                render: (r) => <span dir="ltr">{r.order?.order_number ?? '—'}</span>,
                searchText: (r) => r.order?.order_number ?? '',
            },
            {
                id: 'company',
                key: 'company',
                label: t.col_company ?? 'Company',
                header: t.col_company ?? 'Company',
                render: (r) => localizedName(locale, r.company),
                searchText: (r) => localizedName(locale, r.company),
            },
            {
                id: 'customer',
                key: 'customer',
                label: t.col_customer ?? 'Customer',
                header: t.col_customer ?? 'Customer',
                render: (r) => localizedName(locale, r.customer?.user ?? null),
                searchText: (r) => {
                    const user = r.customer?.user;
                    return `${localizedName(locale, user ?? null)} ${user?.email ?? ''} ${user?.phone ?? ''}`.trim();
                },
            },
            {
                id: 'total',
                key: 'total',
                label: t.col_total ?? 'Total',
                header: t.col_total ?? 'Total',
                render: (r) => <span dir="ltr">{r.total_amount}</span>,
                searchText: (r) => String(r.total_amount ?? ''),
            },
            {
                id: 'created_at',
                key: 'created_at',
                label: t.col_created_at ?? 'Created at',
                header: t.col_created_at ?? 'Created at',
                render: (r) => <span dir="ltr">{formatDate(r.created_at, locale)}</span>,
                searchText: (r) => r.created_at ?? '',
            },
            {
                id: 'status',
                key: 'status',
                label: t.col_status ?? 'Status',
                header: t.col_status ?? 'Status',
                render: (r) => (
                    <StatusBadge variant={invoiceStatusVariant(r.status)} pill>
                        {statusLabels[r.status] ?? r.status}
                    </StatusBadge>
                ),
                searchText: (r) => `${statusLabels[r.status] ?? ''} ${r.status}`.trim(),
            },
        ],
        [locale, statusLabels, t],
    );

    const resolvedVisibleColumnIds = visibleColumnIds.length > 0 ? visibleColumnIds : tableColumns.map((c) => c.id);
    const visibleTableColumns = tableColumns.filter((c) => resolvedVisibleColumnIds.includes(c.id));

    return (
        <PlatformAdminLayout header={<h4 className="m-0 font-weight-bold text-dark">{t.page_title ?? 'Invoices'}</h4>}>
            <Head title={t.page_title ?? 'Invoices'} />

            <div className={hasActiveFilters ? 'card card-outline card-primary' : 'card card-outline card-primary collapsed-card'}>
                <div className="card-header" data-card-widget="collapse" style={{ cursor: 'pointer' }}>
                    <h3 className="card-title">
                        <span>
                            <i className="fas fa-filter" />
                        </span>
                        <span className="mx-1">{actions.filter ?? 'Filter'}</span>
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
                        <div className="col-sm-12 col-md-4 mb-3">
                            <label className="small text-muted">{actions.search ?? 'Search'}</label>
                            <input
                                className="form-control"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder={t.search_placeholder ?? 'Search by invoice, order, company, or customer...'}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            />
                        </div>

                        <div className="col-sm-12 col-md-3 mb-3">
                            <label className="small text-muted">{t.col_status ?? 'Status'}</label>
                            <select className="custom-select" value={status} onChange={(e) => setStatus(e.target.value)}>
                                <option value="">{t.status_all ?? 'All statuses'}</option>
                                {Object.entries(statusLabels).map(([value, label]) => (
                                    <option key={value} value={value}>
                                        {label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="col-sm-12 col-md-2 mb-3">
                            <label className="small text-muted">{t.date_from ?? 'From date'}</label>
                            <input
                                type="date"
                                className="form-control"
                                value={dateFrom}
                                onChange={(e) => setDateFrom(e.target.value)}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            />
                        </div>

                        <div className="col-sm-12 col-md-2 mb-3">
                            <label className="small text-muted">{t.date_to ?? 'To date'}</label>
                            <input
                                type="date"
                                className="form-control"
                                value={dateTo}
                                onChange={(e) => setDateTo(e.target.value)}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            />
                        </div>

                        <div className="col-sm-12" style={{ textAlign: isRtl ? 'right' : 'left' }}>
                            <button type="submit" className="btn btn-primary px-4" disabled={isFiltering}>
                                <i className={isRtl ? 'fas fa-filter ms-1' : 'fas fa-filter me-1'} />
                                {actions.filter ?? 'Filter'}
                            </button>
                            <button
                                type="button"
                                className="btn btn-danger px-4 mx-2"
                                onClick={resetFilters}
                                disabled={isFiltering}
                            >
                                <i className={isRtl ? 'fas fa-undo ms-1' : 'fas fa-undo me-1'} />
                                {actions.reset ?? 'Reset'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <TableCard title={t.list_title ?? 'Invoices list'} bodyPadding={false}>
                <div className="p-3 border-bottom">
                    <TableToolbar<Row>
                        rows={invoices.data}
                        columns={tableColumns}
                        showSearch={false}
                        exportFilename="platform-invoices"
                        persistKey="platform.invoices.index"
                        onVisibleColumnIdsChange={(ids) => setVisibleColumnIds(ids)}
                        showPerPage
                        perPage={perPage}
                        perPageOptions={[10, 25, 50, 100]}
                        onPerPageChange={(value) => {
                            const next = Number(value) || 10;
                            setPerPage(next);
                            applyFilters(next);
                        }}
                    />
                </div>

                <DataTable<Row>
                    columns={visibleTableColumns}
                    rows={invoices.data}
                    rowKey={(r) => r.id}
                    emptyTitle={t.empty ?? 'No invoices found.'}
                />
                <div className="px-3 pb-3">
                    <Pagination
                        links={invoices.links}
                        data={{
                            current_page: invoices.current_page,
                            last_page: invoices.last_page,
                            prev_page_url: invoices.prev_page_url,
                            next_page_url: invoices.next_page_url,
                            from: invoices.from,
                            to: invoices.to,
                            total: invoices.total,
                        }}
                    />
                </div>
            </TableCard>
        </PlatformAdminLayout>
    );
}
