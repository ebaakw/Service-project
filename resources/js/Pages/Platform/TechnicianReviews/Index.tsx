import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import DataTable from '@/Components/ui/DataTable';
import Pagination from '@/Components/ui/Pagination';
import StatusBadge from '@/Components/ui/StatusBadge';
import TableCard from '@/Components/ui/TableCard';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

type CompanyOption = { id: number; name: string };

type ServiceCategory = { id: number; name_ar: string; name_en: string | null };

type TechnicianRow = {
    id: number;
    employee_code: string | null;
    review_status: string | null;
    reviewed_at: string | null;
    rejection_reason: string | null;
    company: { id: number; name: string };
    user: { name: string; email: string | null; phone: string | null };
    service_categories: ServiceCategory[];
    can: { approve: boolean; reject: boolean };
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

export default function TechnicianReviewsIndex({
    technicians,
    filters,
    companies,
}: {
    technicians: Paginator<TechnicianRow>;
    filters: { search: string; review_status: string; company_id?: number | null; per_page?: number };
    companies: CompanyOption[];
}) {
    const page = usePage<PageProps>().props;
    const { direction } = page;
    const isRtl = direction === 'rtl';
    const statusLabels = page.enumLabels?.technician_review_status ?? {};

    const statusBadge = (status: string | null) => {
        const s = (status ?? '').toLowerCase();
        if (s === 'approved') return <StatusBadge variant="success">{statusLabels.approved ?? 'Approved'}</StatusBadge>;
        if (s === 'pending') return <StatusBadge variant="warning">{statusLabels.pending ?? 'Pending'}</StatusBadge>;
        if (s === 'rejected') return <StatusBadge variant="danger">{statusLabels.rejected ?? 'Rejected'}</StatusBadge>;
        return <span className="text-muted">—</span>;
    };

    const [search, setSearch] = useState(filters.search ?? '');
    const [reviewStatus, setReviewStatus] = useState(filters.review_status ?? 'pending');
    const [companyId, setCompanyId] = useState<string>(filters.company_id ? String(filters.company_id) : '');
    const [isFiltering, setIsFiltering] = useState(false);

    const hasActiveFilters = Boolean(search.trim()) || Boolean(companyId) || String(reviewStatus ?? '').toLowerCase() !== 'pending';

    const applyFilters = () => {
        setIsFiltering(true);
        router.get(
            route('platform.technician-reviews.index'),
            {
                search: search || undefined,
                review_status: reviewStatus || undefined,
                company_id: companyId ? Number(companyId) : undefined,
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
        setReviewStatus('pending');
        setCompanyId('');
        setIsFiltering(true);
        router.get(route('platform.technician-reviews.index'), {}, { preserveState: true, preserveScroll: true, replace: true, onFinish: () => setIsFiltering(false) });
    };

    const columns = useMemo(
        () => [
            {
                key: 'technician',
                header: __('tenant.technicians.title'),
                render: (r: TechnicianRow) => (
                    <div>
                        <div className="font-weight-bold">{r.user.name}</div>
                        <div className="text-muted small" dir="ltr">
                            {r.user.email ?? '—'} {r.user.phone ? ` • ${r.user.phone}` : ''}
                        </div>
                        <div className="text-muted small" dir="ltr">
                            {r.employee_code ?? '—'}
                        </div>
                    </div>
                ),
            },
            {
                key: 'company',
                header: __('tenant.technicians.company'),
                render: (r: TechnicianRow) => r.company?.name ?? '—',
            },
            {
                key: 'categories',
                header: __('tenant.technicians.categories'),
                render: (r: TechnicianRow) => r.service_categories.map((c) => c.name_ar).join(', ') || '—',
            },
            {
                key: 'status',
                header: __('tenant.technicians.review_status'),
                render: (r: TechnicianRow) => (
                    <div>
                        {statusBadge(r.review_status)}
                        {String(r.review_status ?? '').toLowerCase() === 'rejected' && r.rejection_reason ? (
                            <div className="text-muted small mt-1">{r.rejection_reason}</div>
                        ) : null}
                    </div>
                ),
            },
            {
                key: 'actions',
                header: <span className="d-block text-end">{__('tenant.technicians.actions')}</span>,
                className: 'text-end',
                render: (r: TechnicianRow) => (
                    <div className="btn-group btn-group-sm">
                        <Link href={route('platform.technician-reviews.show', r.id)} className="btn btn-outline-primary">
                            {__('tenant.technicians.view')}
                        </Link>
                        {r.can?.approve ? (
                            <button
                                type="button"
                                className="btn btn-outline-success"
                                onClick={() => router.post(route('platform.technician-reviews.approve', r.id))}
                            >
                                {__('dashboard.technician_reviews.approve')}
                            </button>
                        ) : null}
                        {r.can?.reject ? (
                            <button
                                type="button"
                                className="btn btn-outline-danger"
                                onClick={() => {
                                    const reason = window.prompt(__('dashboard.technician_reviews.rejection_reason')) || '';
                                    if (reason.trim().length < 3) return;
                                    router.post(route('platform.technician-reviews.reject', r.id), { rejection_reason: reason });
                                }}
                            >
                                {__('dashboard.technician_reviews.reject')}
                            </button>
                        ) : null}
                    </div>
                ),
            },
        ],
        [],
    );

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.technician_reviews"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('tenant.technician_reviews.title')}</h4>}
        >
            <Head title={__('tenant.technician_reviews.title')} />

            <div className={hasActiveFilters ? 'card card-outline card-primary' : 'card card-outline card-primary collapsed-card'}>
                <div className="card-header" data-card-widget="collapse" style={{ cursor: 'pointer' }}>
                    <h3 className="card-title">
                        <i className="fas fa-filter mr-2" />
                        {__('tenant.filter')}
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
                        <div className="col-md-5 mb-3">
                            <input
                                className="form-control"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder={__('tenant.technicians.searchPlaceholder')}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            />
                        </div>
                        <div className="col-md-3 mb-3">
                            <select className="form-control" value={reviewStatus} onChange={(e) => setReviewStatus(e.target.value)}>
                                <option value="pending">{statusLabels.pending ?? 'Pending'}</option>
                                <option value="approved">{statusLabels.approved ?? 'Approved'}</option>
                                <option value="rejected">{statusLabels.rejected ?? 'Rejected'}</option>
                                <option value="all">{__('tenant.technician_reviews.all')}</option>
                            </select>
                        </div>
                        <div className="col-md-4 mb-3">
                            <select className="form-control" value={companyId} onChange={(e) => setCompanyId(e.target.value)}>
                                <option value="">{__('tenant.technician_reviews.all_companies')}</option>
                                {companies.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="col-12" style={{ textAlign: isRtl ? 'right' : 'left' }}>
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

            <TableCard title={__('tenant.technician_reviews.title')} actionsVariant="card-tools" bodyPadding={false}>
                <DataTable<TechnicianRow> columns={columns as any} rows={technicians.data} rowKey={(r) => r.id} emptyTitle={__('tenant.technicians.empty')} />
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

