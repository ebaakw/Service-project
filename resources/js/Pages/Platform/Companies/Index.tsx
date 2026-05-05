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

type CompanyRow = {
    id: number;
    ulid: string;
    display_name: string;
    name_ar: string;
    name_en: string | null;
    slug: string | null;
    logo_url: string | null;
    phone: string | null;
    email: string | null;
    status: string | null;
    status_label: string | null;
    subscription_status: string | null;
    subscription_status_label: string | null;
    subscription_starts_at: string | null;
    subscription_ends_at: string | null;
    is_featured: boolean;
    default_inspection_fee: string | number | null;
    rating_average: string | number | null;
    rating_count: number | null;
    members_count: number;
    technicians_count: number;
    orders_count: number;
    created_at: string | null;
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

type Option = { value: string; label: string };

export default function PlatformCompaniesIndex({
    companies,
    filters,
    enums,
}: {
    companies: Paginator<CompanyRow>;
    filters: { search: string; status: string; subscription_status: string; per_page?: number };
    enums: { company_status: Option[]; subscription_status: Option[] };
}) {
    const { locale, direction } = usePage<PageProps>().props;
    const isRtl = direction === 'rtl';
    const isAr = (locale || '').toLowerCase().startsWith('ar');

    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [subscriptionStatus, setSubscriptionStatus] = useState(filters.subscription_status ?? '');
    const [perPage, setPerPage] = useState<number>(Number(filters.per_page ?? 10) || 10);
    const [isFiltering, setIsFiltering] = useState(false);
    const [visibleColumnIds, setVisibleColumnIds] = useState<string[]>([]);
    const hasActiveFilters = Boolean(
        (filters.search ?? '').trim() || (filters.status ?? '').trim() || (filters.subscription_status ?? '').trim(),
    );
    const exportUrl = route('platform.companies.export', {
        search: search || undefined,
        status: status || undefined,
        subscription_status: subscriptionStatus || undefined,
    });

    const toolbarExportCsvUrl = route('platform.companies.export', {
        search: search || undefined,
        status: status || undefined,
        subscription_status: subscriptionStatus || undefined,
    });

    const statusVariant = useMemo(() => {
        return (value: string | null) => {
            if (value === 'active') return 'success' as const;
            if (value === 'pending') return 'warning' as const;
            if (value === 'suspended') return 'danger' as const;
            if (value === 'inactive') return 'secondary' as const;
            return 'light' as const;
        };
    }, []);

    const subVariant = useMemo(() => {
        return (value: string | null) => {
            if (value === 'active') return 'success' as const;
            if (value === 'trial') return 'info' as const;
            if (value === 'past_due') return 'warning' as const;
            if (value === 'expired') return 'secondary' as const;
            if (value === 'cancelled') return 'danger' as const;
            return 'light' as const;
        };
    }, []);

    const applyFilters = () => {
        setIsFiltering(true);
        router.get(
            route('platform.companies.index'),
            {
                search: search || undefined,
                status: status || undefined,
                subscription_status: subscriptionStatus || undefined,
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
        setStatus('');
        setSubscriptionStatus('');
        setPerPage(10);
        setIsFiltering(true);
        router.get(route('platform.companies.index'), {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => setIsFiltering(false),
        });
    };

    const destroy = (id: number) => {
        if (!confirm(__('tenant.companies.deleteConfirm'))) {
            return;
        }
        router.delete(route('platform.companies.destroy', id), { preserveScroll: true });
    };

    const tableColumns: Array<TableToolbarColumn<CompanyRow> & { key: string; header: React.ReactNode; className?: string; headerClassName?: string }> =
        useMemo(
            () => [
                {
                    id: 'logo',
                    key: 'logo',
                    label: __('tenant.companies.logo'),
                    header: __('tenant.companies.logo'),
                    render: (r) =>
                        r.logo_url ? (
                            <img
                                src={r.logo_url}
                                alt=""
                                style={{ width: 40, height: 40, objectFit: 'contain' }}
                                className="rounded border bg-white"
                            />
                        ) : (
                            <span className="text-muted">—</span>
                        ),
                },
                {
                    id: 'name',
                    key: 'name',
                    label: __('tenant.companies.title'),
                    header: __('tenant.companies.title'),
                    render: (r) => <strong>{r.display_name}</strong>,
                    searchText: (r) => r.display_name ?? '',
                },
                {
                    id: 'slug',
                    key: 'slug',
                    label: __('tenant.companies.slug'),
                    header: __('tenant.companies.slug'),
                    render: (r) => <span dir="ltr">{r.slug ?? '—'}</span>,
                    searchText: (r) => r.slug ?? '',
                },
                {
                    id: 'contact',
                    key: 'contact',
                    label: __('tenant.companies.phone'),
                    header: __('tenant.companies.phone'),
                    render: (r) => (
                        <div dir="ltr">
                            <div>{r.phone || '—'}</div>
                            <div className="text-muted">{r.email || '—'}</div>
                        </div>
                    ),
                    searchText: (r) => `${r.phone ?? ''} ${r.email ?? ''}`.trim(),
                },
                {
                    id: 'status',
                    key: 'status',
                    label: __('tenant.companies.status'),
                    header: __('tenant.companies.status'),
                    render: (r) => (
                        <StatusBadge variant={statusVariant(r.status)} pill>
                            {r.status_label ?? r.status ?? '—'}
                        </StatusBadge>
                    ),
                    searchText: (r) => `${r.status_label ?? ''} ${r.status ?? ''}`.trim(),
                },
                {
                    id: 'subscription',
                    key: 'subscription',
                    label: __('tenant.companies.subscriptionStatus'),
                    header: __('tenant.companies.subscriptionStatus'),
                    render: (r) => (
                        <div className="d-flex flex-column gap-1">
                            <StatusBadge variant={subVariant(r.subscription_status)} pill>
                                {r.subscription_status_label ?? r.subscription_status ?? '—'}
                            </StatusBadge>
                            <span className="text-muted small" dir="ltr">
                                {r.subscription_starts_at ?? '—'} → {r.subscription_ends_at ?? '—'}
                            </span>
                        </div>
                    ),
                    searchText: (r) => `${r.subscription_status_label ?? ''} ${r.subscription_status ?? ''}`.trim(),
                },
                {
                    id: 'counts',
                    key: 'counts',
                    label: <span dir="ltr">M/T/O</span>,
                    header: <span dir="ltr">M/T/O</span>,
                    render: (r) => (
                        <div className="text-muted small" dir="ltr">
                            M:{r.members_count} · T:{r.technicians_count} · O:{r.orders_count}
                        </div>
                    ),
                    searchText: (r) => `M:${r.members_count} T:${r.technicians_count} O:${r.orders_count}`,
                },
                {
                    id: 'actions',
                    key: 'actions',
                    label: __('tenant.companies.actions'),
                    header: <span className="d-block text-end">{__('tenant.companies.actions')}</span>,
                    className: 'text-end',
                    render: (r) => (
                        <div className="btn-group">
                            <Link href={route('platform.companies.edit', r.id)} className="btn btn-outline-primary btn-sm">
                                {__('tenant.companies.edit')}
                            </Link>
                            <button type="button" className="btn btn-outline-danger btn-sm" onClick={() => destroy(r.id)}>
                                {__('tenant.companies.delete')}
                            </button>
                        </div>
                    ),
                },
            ],
            [destroy, statusVariant, subVariant, __],
        );

    const resolvedVisibleColumnIds = visibleColumnIds.length > 0 ? visibleColumnIds : tableColumns.map((c) => c.id);
    const visibleTableColumns = tableColumns.filter((c) => resolvedVisibleColumnIds.includes(c.id));

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.companies"
            header={
                <h4 className="m-0 font-weight-bold text-dark">{__('tenant.companies.title')}</h4>
            }
        >
            <Head title={__('tenant.companies.title')} />

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
                                placeholder={
                                    isAr
                                        ? 'بحث بالاسم أو الهاتف أو البريد أو المعرف النصي...'
                                        : 'Search by name, phone, email, or slug...'
                                }
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            />
                        </div>

                        <div className="col-sm-6 col-md-3 col-lg-3">
                            <select
                                className="form-control"
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            >
                                <option value="">{isAr ? 'الحالة - الكل' : 'Status - All'}</option>
                                {enums.company_status.map((o) => (
                                    <option key={o.value} value={o.value}>
                                        {o.label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="col-sm-6 col-md-3 col-lg-3">
                            <select
                                className="form-control"
                                value={subscriptionStatus}
                                onChange={(e) => setSubscriptionStatus(e.target.value)}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            >
                                <option value="">{isAr ? 'حالة الاشتراك - الكل' : 'Subscription Status - All'}</option>
                                {enums.subscription_status.map((o) => (
                                    <option key={o.value} value={o.value}>
                                        {o.label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="col-sm-12 mt-3" style={{ textAlign: isRtl ? 'right' : 'left' }}>
                            <button type="submit" className="btn btn-primary px-4" disabled={isFiltering}>
                                <i className={isRtl ? 'fas fa-filter ms-1' : 'fas fa-filter me-1'} />
                                {isAr ? 'تصفية' : 'Filter'}
                            </button>
                            <button
                                type="button"
                                className="btn btn-danger px-4 mx-2"
                                onClick={resetFilters}
                                disabled={isFiltering}
                            >
                                <i className={isRtl ? 'fas fa-undo ms-1' : 'fas fa-undo me-1'} />
                                {isAr ? 'إعادة تعيين' : 'Reset'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <TableCard
                title={__('tenant.companies.list')}
                actions={
                    <Link href={route('platform.companies.create')} className="btn btn-success btn-sm">
                        <i className="fas fa-plus mr-1" />
                        {__('tenant.companies.create')}
                    </Link>
                }
                actionsVariant="card-tools"
                bodyPadding={false}
            >
                <div className="p-3 border-bottom">
                    <TableToolbar<CompanyRow>
                        rows={companies.data}
                        columns={tableColumns}
                        showSearch={false}
                        exportCsvUrl={toolbarExportCsvUrl}
                        exportFilename="platform-companies"
                        persistKey="platform.companies.index"
                        onVisibleColumnIdsChange={(ids) => setVisibleColumnIds(ids)}
                        showPerPage
                        perPage={perPage}
                        perPageOptions={[10, 25, 50, 100]}
                        onPerPageChange={(value) => {
                            const next = Number(value) || 10;
                            setPerPage(next);
                            setIsFiltering(true);
                            router.get(
                                route('platform.companies.index'),
                                {
                                    search: search || undefined,
                                    status: status || undefined,
                                    subscription_status: subscriptionStatus || undefined,
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

                <DataTable<CompanyRow>
                    columns={visibleTableColumns}
                    rows={companies.data}
                    rowKey={(r) => r.id}
                    emptyTitle={__('tenant.companies.empty')}
                />
                <div className="px-3 pb-3">
                    <Pagination
                        links={companies.links}
                        data={{
                            current_page: companies.current_page,
                            last_page: companies.last_page,
                            prev_page_url: companies.prev_page_url,
                            next_page_url: companies.next_page_url,
                            from: companies.from,
                            to: companies.to,
                            total: companies.total,
                        }}
                    />
                </div>
            </TableCard>
        </PlatformAdminLayout>
    );
}
