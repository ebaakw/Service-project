import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import DataTable from '@/Components/ui/DataTable';
import Pagination from '@/Components/ui/Pagination';
import StatusBadge from '@/Components/ui/StatusBadge';
import TableCard from '@/Components/ui/TableCard';
import type { TableToolbarColumn } from '@/Components/ui/TableToolbar';
import TableToolbar from '@/Components/ui/TableToolbar';
import type { PageProps } from '@/types';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, router, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useMemo, useState } from 'react';

type ReportRow = {
    id: number;
    order_number: string;
    status: string;
    assigned_at: string | null;
    accepted_at: string | null;
    arrived_at: string | null;
    response_seconds: number | null;
    arrival_seconds: number | null;
    response_late: boolean;
    arrival_late: boolean;
    company: {
        id: number;
        name: string;
        name_ar?: string | null;
        name_en?: string | null;
    } | null;
    customer: {
        user: {
            name: string;
            name_ar?: string | null;
            name_en?: string | null;
            email?: string | null;
            phone?: string | null;
        } | null;
    } | null;
    technician: {
        id: number;
        employee_code?: string | null;
        availability_status?: string | null;
        user: { name: string; name_ar?: string | null; name_en?: string | null } | null;
    } | null;
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
    technician_id: number | string;
    company_id: number | string;
    date_from: string;
    date_to: string;
    late_only: boolean;
    per_page?: number;
};

type Option = { id: number; label: string };

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

function formatDuration(seconds: number | null): string {
    if (seconds === null || Number.isNaN(seconds)) {
        return '—';
    }
    const total = Math.max(0, Math.floor(seconds));
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60);
    const s = total % 60;
    if (h > 0) {
        return `${h}h ${m}m`;
    }
    if (m > 0) {
        return `${m}m ${s}s`;
    }
    return `${s}s`;
}

function formatDateTime(value: string | null, locale: string): string {
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
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}

export default function PlatformTechnicianReport({
    rows,
    filters,
    technicians,
    companies,
    sla,
}: {
    rows: Paginated<ReportRow>;
    filters: Filters;
    technicians: Option[];
    companies: Option[];
    sla: { accept_minutes: number; arrival_minutes: number };
}) {
    const page = usePage<PageProps>().props;
    const t = (page.t as any)?.technician_report ?? {};
    const actions = page.t?.actions ?? {};
    const locale = page.locale;
    const isRtl = page.direction === 'rtl';
    const statusLabels = page.enumLabels?.order_status ?? {};
    const availabilityLabels = page.enumLabels?.technician_availability ?? {};

    const [search, setSearch] = useState(filters.search ?? '');
    const [technicianId, setTechnicianId] = useState(String(filters.technician_id ?? ''));
    const [companyId, setCompanyId] = useState(String(filters.company_id ?? ''));
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');
    const [lateOnly, setLateOnly] = useState(Boolean(filters.late_only));
    const [perPage, setPerPage] = useState<number>(Number(filters.per_page ?? 10) || 10);
    const [isFiltering, setIsFiltering] = useState(false);
    const [visibleColumnIds, setVisibleColumnIds] = useState<string[]>([]);

    const hasActiveFilters = Boolean(
        (filters.search ?? '').trim()
            || String(filters.technician_id ?? '').trim()
            || String(filters.company_id ?? '').trim()
            || (filters.date_from ?? '').trim()
            || (filters.date_to ?? '').trim()
            || filters.late_only,
    );

    const applyFilters = (nextPerPage = perPage) => {
        setIsFiltering(true);
        router.get(
            route('platform.reports.technicians'),
            {
                search: search || undefined,
                technician_id: technicianId || undefined,
                company_id: companyId || undefined,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
                late_only: lateOnly ? 1 : undefined,
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
        setTechnicianId('');
        setCompanyId('');
        setDateFrom('');
        setDateTo('');
        setLateOnly(false);
        setPerPage(10);
        setIsFiltering(true);
        router.get(
            route('platform.reports.technicians'),
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
        TableToolbarColumn<ReportRow> & {
            key: string;
            header: ReactNode;
            className?: string;
        }
    > = useMemo(
        () => [
            {
                id: 'company',
                key: 'company',
                label: t.col_company ?? __('dashboard.technician_report.col_company'),
                header: t.col_company ?? __('dashboard.technician_report.col_company'),
                render: (r) => localizedName(locale, r.company),
                searchText: (r) => localizedName(locale, r.company),
            },
            {
                id: 'order',
                key: 'order',
                label: t.col_order ?? __('dashboard.technician_report.col_order'),
                header: t.col_order ?? __('dashboard.technician_report.col_order'),
                render: (r) => (
                    <Link href={route('platform.orders.index', { search: r.order_number })} className="font-weight-bold" dir="ltr">
                        {r.order_number}
                    </Link>
                ),
                searchText: (r) => r.order_number ?? '',
            },
            {
                id: 'customer',
                key: 'customer',
                label: t.col_customer ?? __('dashboard.technician_report.col_customer'),
                header: t.col_customer ?? __('dashboard.technician_report.col_customer'),
                render: (r) => localizedName(locale, r.customer?.user ?? null),
                searchText: (r) => localizedName(locale, r.customer?.user ?? null),
            },
            {
                id: 'technician',
                key: 'technician',
                label: t.col_technician ?? __('dashboard.technician_report.col_technician'),
                header: t.col_technician ?? __('dashboard.technician_report.col_technician'),
                render: (r) => {
                    const name = localizedName(locale, r.technician?.user ?? null);
                    const code = r.technician?.employee_code;
                    return (
                        <div>
                            <div>{name}</div>
                            {code ? (
                                <small className="text-muted" dir="ltr">
                                    {code}
                                </small>
                            ) : null}
                        </div>
                    );
                },
                searchText: (r) =>
                    `${localizedName(locale, r.technician?.user ?? null)} ${r.technician?.employee_code ?? ''}`.trim(),
            },
            {
                id: 'availability',
                key: 'availability',
                label: t.col_availability ?? __('dashboard.technician_report.col_availability'),
                header: t.col_availability ?? __('dashboard.technician_report.col_availability'),
                render: (r) => {
                    const value = r.technician?.availability_status ?? '';
                    return availabilityLabels[value] ?? value ?? '—';
                },
                searchText: (r) => r.technician?.availability_status ?? '',
            },
            {
                id: 'response',
                key: 'response',
                label: t.col_response ?? __('dashboard.technician_report.col_response'),
                header: t.col_response ?? __('dashboard.technician_report.col_response'),
                render: (r) => (
                    <div>
                        <div dir="ltr">{formatDuration(r.response_seconds)}</div>
                        <StatusBadge variant={r.response_late ? 'danger' : 'success'} pill>
                            {r.response_late
                                ? t.late ?? __('dashboard.technician_report.late')
                                : t.on_time ?? __('dashboard.technician_report.on_time')}
                        </StatusBadge>
                    </div>
                ),
                searchText: (r) => formatDuration(r.response_seconds),
            },
            {
                id: 'arrival',
                key: 'arrival',
                label: t.col_arrival ?? __('dashboard.technician_report.col_arrival'),
                header: t.col_arrival ?? __('dashboard.technician_report.col_arrival'),
                render: (r) => (
                    <div>
                        <div dir="ltr">{formatDuration(r.arrival_seconds)}</div>
                        <StatusBadge variant={r.arrival_late ? 'danger' : 'success'} pill>
                            {r.arrival_late
                                ? t.late ?? __('dashboard.technician_report.late')
                                : t.on_time ?? __('dashboard.technician_report.on_time')}
                        </StatusBadge>
                    </div>
                ),
                searchText: (r) => formatDuration(r.arrival_seconds),
            },
            {
                id: 'assigned_at',
                key: 'assigned_at',
                label: t.col_assigned_at ?? __('dashboard.technician_report.col_assigned_at'),
                header: t.col_assigned_at ?? __('dashboard.technician_report.col_assigned_at'),
                render: (r) => <span dir="ltr">{formatDateTime(r.assigned_at, locale)}</span>,
                searchText: (r) => r.assigned_at ?? '',
            },
            {
                id: 'status',
                key: 'status',
                label: t.col_status ?? __('dashboard.technician_report.col_status'),
                header: t.col_status ?? __('dashboard.technician_report.col_status'),
                render: (r) => (
                    <StatusBadge variant={r.response_late || r.arrival_late ? 'warning' : 'info'} pill>
                        {statusLabels[r.status] ?? r.status}
                    </StatusBadge>
                ),
                searchText: (r) => `${statusLabels[r.status] ?? ''} ${r.status}`.trim(),
            },
        ],
        [availabilityLabels, locale, statusLabels, t],
    );

    const resolvedVisibleColumnIds = visibleColumnIds.length > 0 ? visibleColumnIds : tableColumns.map((c) => c.id);
    const visibleTableColumns = tableColumns.filter((c) => resolvedVisibleColumnIds.includes(c.id));

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.technicians_report"
            header={<h1 className="m-0 font-weight-bold text-dark">{t.page_title ?? __('dashboard.technician_report.page_title')}</h1>}
        >
            <Head title={t.page_title ?? __('dashboard.technician_report.page_title')} />

            <div className="alert alert-light border mb-3">
                <strong>{t.sla_summary ?? __('dashboard.technician_report.sla_summary')}</strong>
                <span className="mx-2">
                    {t.accept_sla ?? __('dashboard.technician_report.accept_sla')}: {sla.accept_minutes}{' '}
                    {t.minutes ?? __('dashboard.technician_report.minutes')}
                </span>
                <span>
                    {t.arrival_sla ?? __('dashboard.technician_report.arrival_sla')}: {sla.arrival_minutes}{' '}
                    {t.minutes ?? __('dashboard.technician_report.minutes')}
                </span>
            </div>

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
                        <div className="col-sm-12 col-md-3 mb-3">
                            <input
                                className="form-control"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder={t.search_placeholder ?? __('dashboard.technician_report.search_placeholder')}
                                aria-label={t.search_placeholder ?? __('dashboard.technician_report.search_placeholder')}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            />
                        </div>
                        <div className="col-sm-12 col-md-3 mb-3">
                            <label className="small text-muted">{t.col_company ?? __('dashboard.technician_report.col_company')}</label>
                            <select
                                className="custom-select"
                                value={companyId}
                                onChange={(e) => setCompanyId(e.target.value)}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            >
                                <option value="">{t.company_all ?? __('dashboard.technician_report.company_all')}</option>
                                {companies.map((company) => (
                                    <option key={company.id} value={company.id}>
                                        {company.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="col-sm-12 col-md-3 mb-3">
                            <label className="small text-muted">{t.col_technician ?? __('dashboard.technician_report.col_technician')}</label>
                            <select
                                className="custom-select"
                                value={technicianId}
                                onChange={(e) => setTechnicianId(e.target.value)}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            >
                                <option value="">{t.technician_all ?? __('dashboard.technician_report.technician_all')}</option>
                                {technicians.map((tech) => (
                                    <option key={tech.id} value={tech.id}>
                                        {tech.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="col-sm-12 col-md-2 mb-3">
                            <label className="small text-muted">{t.date_from ?? __('dashboard.technician_report.date_from')}</label>
                            <input
                                type="date"
                                className="form-control"
                                value={dateFrom}
                                onChange={(e) => setDateFrom(e.target.value)}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            />
                        </div>
                        <div className="col-sm-12 col-md-2 mb-3">
                            <label className="small text-muted">{t.date_to ?? __('dashboard.technician_report.date_to')}</label>
                            <input
                                type="date"
                                className="form-control"
                                value={dateTo}
                                onChange={(e) => setDateTo(e.target.value)}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            />
                        </div>
                        <div className="col-sm-12 col-md-3 mb-3 d-flex align-items-end">
                            <div className="custom-control custom-checkbox">
                                <input
                                    type="checkbox"
                                    className="custom-control-input"
                                    id="late_only_platform"
                                    checked={lateOnly}
                                    onChange={(e) => setLateOnly(e.target.checked)}
                                />
                                <label className="custom-control-label" htmlFor="late_only_platform">
                                    {t.late_only ?? __('dashboard.technician_report.late_only')}
                                </label>
                            </div>
                        </div>
                        <div className="col-sm-12 mt-3" style={{ textAlign: isRtl ? 'right' : 'left' }}>
                            <button type="submit" className="btn btn-primary px-4" disabled={isFiltering}>
                                <i className="fas fa-filter mx-1" />
                                {actions.filter ?? 'Filter'}
                            </button>
                            <button type="button" className="btn btn-danger px-4 mx-2" onClick={resetFilters} disabled={isFiltering}>
                                <i className="fas fa-undo mx-1" />
                                {actions.reset ?? 'Reset'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <TableCard
                title={t.list_title ?? __('dashboard.technician_report.list_title')}
                actionsVariant="card-tools"
                bodyPadding={false}
            >
                <div className="p-3 border-bottom">
                    <TableToolbar<ReportRow>
                        rows={rows.data}
                        columns={tableColumns}
                        showSearch={false}
                        exportFilename="platform-technician-report"
                        persistKey="platform.reports.technicians"
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
                <DataTable<ReportRow>
                    columns={visibleTableColumns}
                    rows={rows.data}
                    rowKey={(r) => r.id}
                    emptyTitle={t.empty ?? __('dashboard.technician_report.empty')}
                />
                <div className="px-3 pb-3">
                    <Pagination
                        links={rows.links}
                        data={{
                            current_page: rows.current_page,
                            last_page: rows.last_page,
                            prev_page_url: rows.prev_page_url,
                            next_page_url: rows.next_page_url,
                            from: rows.from,
                            to: rows.to,
                            total: rows.total,
                        }}
                    />
                </div>
            </TableCard>
        </PlatformAdminLayout>
    );
}
