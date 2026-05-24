import DataTable from '@/Components/ui/DataTable';
import Pagination from '@/Components/ui/Pagination';
import StatusBadge from '@/Components/ui/StatusBadge';
import TableCard from '@/Components/ui/TableCard';
import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import PortfolioProjectDetailsModal, { type PortfolioProjectDetails } from '../Portfolio/PortfolioProjectDetailsModal';
import RejectPortfolioProjectModal from './RejectPortfolioProjectModal';

type CompanyOption = { id: number; name: string | null };
type Category = { id: number; name_ar: string; name_en: string | null };

type PortfolioReviewRow = PortfolioProjectDetails & {
    is_active: boolean;
    is_featured: boolean;
    review_status: string | null;
    reviewed_at: string | null;
    rejection_reason: string | null;
    created_at: string | null;
    thumbnail_url: string | null;
    images_count: number;
    company: { id: number; name: string | null; slug: string | null } | null;
    service_category: Category | null;
    technicians: { id: number; user: { name: string | null } }[];
    technician: { id: number; user: { name: string | null } } | null;
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

function localizedTitle(project: PortfolioReviewRow, locale: string): string {
    if (locale === 'en') {
        return project.title_en || project.title_ar;
    }

    return project.title_ar || project.title_en || '—';
}

function localizedCategoryName(category: Category | null, locale: string): string {
    if (!category) {
        return '—';
    }

    if (locale === 'en') {
        return category.name_en || category.name_ar;
    }

    return category.name_ar || category.name_en || '—';
}

function technicianNames(project: PortfolioReviewRow): string {
    const names = (project.technicians ?? [])
        .map((technician) => technician.user.name)
        .filter(Boolean);

    return names.length > 0 ? names.join(', ') : __('dashboard.portfolio_projects.no_technician');
}

function companyDisplayName(company: { name: string | null } | null): string {
    const name = company?.name?.trim();

    return name || '—';
}

export default function PortfolioReviewsIndex({
    projects,
    filters,
    companies,
}: {
    projects: Paginator<PortfolioReviewRow>;
    filters: { search: string; review_status: string; company_id?: number | null; per_page?: number };
    companies: CompanyOption[];
}) {
    const page = usePage<PageProps>().props;
    const { locale, direction } = page;
    const isRtl = direction === 'rtl';
    const statusLabels = page.enumLabels?.portfolio_project_review_status ?? {};
    const iconSpacingClass = isRtl ? 'ms-1' : 'me-1';

    const [search, setSearch] = useState(filters.search ?? '');
    const [reviewStatus, setReviewStatus] = useState(filters.review_status ?? 'pending');
    const [companyId, setCompanyId] = useState<string>(filters.company_id ? String(filters.company_id) : '');
    const [isFiltering, setIsFiltering] = useState(false);
    const [rejectingProject, setRejectingProject] = useState<PortfolioReviewRow | null>(null);
    const [viewingProject, setViewingProject] = useState<PortfolioReviewRow | null>(null);
    const [isRejecting, setIsRejecting] = useState(false);

    const hasActiveFilters = Boolean(search.trim()) || Boolean(companyId) || String(reviewStatus ?? '').toLowerCase() !== 'pending';

    const statusBadge = (status: string | null) => {
        const value = (status ?? '').toLowerCase();
        if (value === 'approved') return <StatusBadge variant="success">{statusLabels.approved ?? 'Approved'}</StatusBadge>;
        if (value === 'pending') return <StatusBadge variant="warning">{statusLabels.pending ?? 'Pending'}</StatusBadge>;
        if (value === 'rejected') return <StatusBadge variant="danger">{statusLabels.rejected ?? 'Rejected'}</StatusBadge>;
        return <span className="text-muted">—</span>;
    };

    const applyFilters = () => {
        setIsFiltering(true);
        router.get(
            route('platform.portfolio-reviews.index'),
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
        router.get(
            route('platform.portfolio-reviews.index'),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => setIsFiltering(false),
            },
        );
    };

    const submitRejection = (reason: string) => {
        if (!rejectingProject) return;

        router.post(
            route('platform.portfolio-reviews.reject', rejectingProject.id),
            { rejection_reason: reason },
            {
                preserveScroll: true,
                onStart: () => setIsRejecting(true),
                onSuccess: () => setRejectingProject(null),
                onFinish: () => setIsRejecting(false),
            },
        );
    };

    const columns = useMemo(
        () => [
            {
                key: 'project',
                header: __('dashboard.portfolio_reviews.project'),
                render: (project: PortfolioReviewRow) => (
                    <div className="d-flex align-items-center">
                        <button
                            type="button"
                            className="btn border-0 bg-transparent p-0"
                            onClick={() => setViewingProject(project)}
                            title={__('dashboard.portfolio_projects.view_details')}
                        >
                            {project.thumbnail_url ? (
                                <img
                                    src={project.thumbnail_url}
                                    alt={localizedTitle(project, locale)}
                                    className="rounded border bg-white"
                                    style={{ width: 72, height: 54, objectFit: 'cover' }}
                                />
                            ) : (
                                <div
                                    className="rounded border bg-light d-flex align-items-center justify-content-center text-muted"
                                    style={{ width: 72, height: 54 }}
                                    aria-label={__('dashboard.portfolio_projects.no_image')}
                                >
                                    <i className="far fa-image" />
                                </div>
                            )}
                        </button>
                        <div className="mx-2">
                            <div className="font-weight-bold">{localizedTitle(project, locale)}</div>
                            <div className="text-muted small">
                                <i className={`fas fa-tags ${iconSpacingClass}`} />
                                {localizedCategoryName(project.service_category, locale)}
                            </div>
                            <div className="text-muted small">
                                <i className={`fas fa-user-cog ${iconSpacingClass}`} />
                                {technicianNames(project)}
                            </div>
                        </div>
                    </div>
                ),
            },
            {
                key: 'company',
                header: __('dashboard.portfolio_reviews.company'),
                render: (project: PortfolioReviewRow) => (
                    <div className="font-weight-bold text-dark">
                        {companyDisplayName(project.company)}
                    </div>
                ),
            },
            {
                key: 'status',
                header: __('dashboard.portfolio_reviews.review_status'),
                render: (project: PortfolioReviewRow) => (
                    <div>
                        {statusBadge(project.review_status)}
                        {String(project.review_status ?? '').toLowerCase() === 'rejected' && project.rejection_reason ? (
                            <div className="text-muted small mt-1">{project.rejection_reason}</div>
                        ) : null}
                        <div className="text-muted small mt-1" dir="ltr">
                            {project.created_at ? new Date(project.created_at).toLocaleString() : '—'}
                        </div>
                    </div>
                ),
            },
            {
                key: 'images',
                header: __('dashboard.portfolio_reviews.images'),
                render: (project: PortfolioReviewRow) => (
                    <span dir="ltr">
                        {__('dashboard.portfolio_projects.photos_count', {
                            count: String(project.images_count),
                        })}
                    </span>
                ),
            },
            {
                key: 'actions',
                header: <span className="d-block text-end">{__('dashboard.portfolio_reviews.actions')}</span>,
                className: 'text-end',
                render: (project: PortfolioReviewRow) => (
                    <div className="btn-group btn-group-sm">
                        <button type="button" className="btn btn-outline-info" onClick={() => setViewingProject(project)}>
                            <i className={`far fa-eye ${iconSpacingClass}`} />
                            {__('dashboard.portfolio_projects.view_details')}
                        </button>
                        <Link href={route('platform.portfolio-projects.edit', project.id)} className="btn btn-outline-primary">
                            {__('dashboard.actions.edit')}
                        </Link>
                        {project.can?.approve ? (
                            <button
                                type="button"
                                className="btn btn-outline-success"
                                onClick={() => router.post(route('platform.portfolio-reviews.approve', project.id))}
                            >
                                {__('dashboard.portfolio_reviews.approve')}
                            </button>
                        ) : null}
                        {project.can?.reject ? (
                            <button
                                type="button"
                                className="btn btn-outline-danger"
                                onClick={() => setRejectingProject(project)}
                            >
                                {__('dashboard.portfolio_reviews.reject')}
                            </button>
                        ) : null}
                    </div>
                ),
            },
        ],
        [iconSpacingClass, locale, statusLabels],
    );

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.pending_portfolio_reviews"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('dashboard.portfolio_reviews.title')}</h4>}
        >
            <Head title={__('dashboard.portfolio_reviews.title')} />

            <div className={hasActiveFilters ? 'card card-outline card-primary' : 'card card-outline card-primary collapsed-card'}>
                <div className="card-header" data-card-widget="collapse" style={{ cursor: 'pointer' }}>
                    <h3 className="card-title">
                        <i className={`fas fa-filter ${iconSpacingClass}`} />
                        {__('dashboard.portfolio_projects.filters_title')}
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
                        onSubmit={(event) => {
                            event.preventDefault();
                            applyFilters();
                        }}
                    >
                        <div className="col-md-5 mb-3">
                            <input
                                className="form-control"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder={__('dashboard.portfolio_projects.search_placeholder')}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            />
                        </div>
                        <div className="col-md-3 mb-3">
                            <select className="custom-select" value={reviewStatus} onChange={(event) => setReviewStatus(event.target.value)}>
                                <option value="pending">{statusLabels.pending ?? 'Pending'}</option>
                                <option value="approved">{statusLabels.approved ?? 'Approved'}</option>
                                <option value="rejected">{statusLabels.rejected ?? 'Rejected'}</option>
                                <option value="all">{__('dashboard.portfolio_reviews.all')}</option>
                            </select>
                        </div>
                        <div className="col-md-4 mb-3">
                            <select className="custom-select" value={companyId} onChange={(event) => setCompanyId(event.target.value)}>
                                <option value="">{__('dashboard.portfolio_reviews.all_companies')}</option>
                                {companies.map((company) => (
                                    <option key={company.id} value={company.id}>
                                        {companyDisplayName(company)}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="col-12" style={{ textAlign: isRtl ? 'right' : 'left' }}>
                            <button type="submit" className="btn btn-primary px-4" disabled={isFiltering}>
                                <i className={`fas fa-filter ${iconSpacingClass}`} />
                                {__('dashboard.actions.filter')}
                            </button>
                            <button type="button" className="btn btn-danger px-4 mx-2" onClick={resetFilters} disabled={isFiltering}>
                                <i className={`fas fa-undo ${iconSpacingClass}`} />
                                {__('dashboard.actions.reset')}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <TableCard title={__('dashboard.portfolio_reviews.list_title')} actionsVariant="card-tools" bodyPadding={false}>
                <DataTable<PortfolioReviewRow>
                    columns={columns as any}
                    rows={projects.data}
                    rowKey={(project) => project.id}
                    emptyTitle={__('dashboard.portfolio_reviews.empty')}
                />
                <div className="px-3 pb-3">
                    <Pagination
                        links={projects.links}
                        data={{
                            current_page: projects.current_page,
                            last_page: projects.last_page,
                            prev_page_url: projects.prev_page_url,
                            next_page_url: projects.next_page_url,
                            from: projects.from,
                            to: projects.to,
                            total: projects.total,
                        }}
                    />
                </div>
            </TableCard>

            <PortfolioProjectDetailsModal show={viewingProject !== null} project={viewingProject} onClose={() => setViewingProject(null)} />

            <RejectPortfolioProjectModal
                show={rejectingProject !== null}
                projectTitle={rejectingProject ? localizedTitle(rejectingProject, locale) : null}
                processing={isRejecting}
                onClose={() => {
                    if (!isRejecting) setRejectingProject(null);
                }}
                onConfirm={submitRejection}
            />
        </PlatformAdminLayout>
    );
}
