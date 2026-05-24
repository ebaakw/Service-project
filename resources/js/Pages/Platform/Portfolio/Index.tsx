import DataTable, { type DataTableColumn } from '@/Components/ui/DataTable';
import Pagination from '@/Components/ui/Pagination';
import StatusBadge from '@/Components/ui/StatusBadge';
import TableCard from '@/Components/ui/TableCard';
import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import PortfolioProjectDetailsModal, { type PortfolioProjectDetails } from './PortfolioProjectDetailsModal';

type Row = PortfolioProjectDetails & {
    location: string | null;
    completed_at: string | null;
    is_featured: boolean;
    is_active: boolean;
    thumbnail_url: string | null;
    images_count: number;
    company: { id: number; name: string | null; slug?: string | null } | null;
    service_category: { id: number; name_ar: string; name_en: string | null } | null;
    technicians: { id: number; user: { name: string | null } }[];
    technician: { id: number; user: { name: string | null } } | null;
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

type CompanyOption = { id: number; name: string | null };
type Filters = { search: string; company_id: string | number | null };

function localizedTitle(project: Row, locale: string): string {
    if (locale === 'en') {
        return project.title_en || project.title_ar;
    }

    return project.title_ar || project.title_en || '—';
}

function localizedCategoryName(category: Row['service_category'], locale: string): string {
    if (!category) {
        return '—';
    }

    if (locale === 'en') {
        return category.name_en || category.name_ar;
    }

    return category.name_ar || category.name_en || '—';
}

function technicianNames(project: Row): string {
    const names = (project.technicians ?? [])
        .map((technician) => technician.user.name)
        .filter(Boolean);

    return names.length > 0 ? names.join(', ') : __('dashboard.portfolio_projects.no_technician');
}

function companyDisplayName(company: { name: string | null } | null): string {
    const name = company?.name?.trim();

    return name || '—';
}

export default function PlatformPortfolioIndex({
    projects,
    companies,
    filters,
}: {
    projects: Paginated<Row>;
    companies: CompanyOption[];
    filters: Filters;
}) {
    const { locale, direction } = usePage<PageProps>().props;
    const isRtl = direction === 'rtl';
    const iconSpacingClass = isRtl ? 'ms-1' : 'me-1';

    const [search, setSearch] = useState(filters.search ?? '');
    const [companyId, setCompanyId] = useState(String(filters.company_id ?? ''));
    const [isFiltering, setIsFiltering] = useState(false);
    const [viewingProject, setViewingProject] = useState<Row | null>(null);

    const hasActiveFilters = Boolean((filters.search ?? '').trim() || String(filters.company_id ?? '').trim());
    const selectedCompany = useMemo(
        () => companies.find((company) => String(company.id) === companyId) ?? null,
        [companies, companyId],
    );

    const applyFilters = () => {
        setIsFiltering(true);
        router.get(
            route('platform.portfolio-projects.index'),
            {
                search: search || undefined,
                company_id: companyId || undefined,
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
        setCompanyId('');
        setIsFiltering(true);
        router.get(
            route('platform.portfolio-projects.index'),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => setIsFiltering(false),
            },
        );
    };

    const totalProjects = projects.total ?? projects.data.length;
    const columns = useMemo<Array<DataTableColumn<Row>>>(
        () => [
            {
                key: 'project',
                header: __('dashboard.portfolio_reviews.project'),
                render: (project) => (
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
                            <div className="d-flex flex-wrap align-items-center">
                                <span className="font-weight-bold text-dark">{localizedTitle(project, locale)}</span>
                                {project.is_featured ? (
                                    <StatusBadge variant="warning" pill>
                                        <i className={`fas fa-star ${iconSpacingClass}`} />
                                        {__('dashboard.portfolio_projects.featured')}
                                    </StatusBadge>
                                ) : null}
                            </div>
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
                header: __('dashboard.portfolio_projects.company'),
                render: (project) => (
                    <div className="font-weight-bold text-dark">
                        {companyDisplayName(project.company)}
                    </div>
                ),
            },
            {
                key: 'status',
                header: __('dashboard.portfolio_projects.active'),
                render: (project) => (
                    <div>
                        <StatusBadge variant={project.is_active ? 'success' : 'secondary'} pill>
                            {project.is_active ? __('dashboard.status.active') : __('dashboard.status.inactive')}
                        </StatusBadge>
                        <div className="text-muted small mt-1">
                            <i className={`far fa-calendar-check ${iconSpacingClass}`} />
                            {project.completed_at ?? __('dashboard.portfolio_projects.no_completion_date')}
                        </div>
                    </div>
                ),
            },
            {
                key: 'images',
                header: __('dashboard.portfolio_reviews.images'),
                render: (project) => (
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
                render: (project) => (
                    <div className="btn-group btn-group-sm">
                        <button type="button" className="btn btn-outline-info" onClick={() => setViewingProject(project)}>
                            <i className={`far fa-eye ${iconSpacingClass}`} />
                            {__('dashboard.portfolio_projects.view_details')}
                        </button>
                        <Link href={route('platform.portfolio-projects.edit', project.id)} className="btn btn-outline-primary">
                            <i className={`fas fa-pen ${iconSpacingClass}`} />
                            {__('dashboard.actions.edit')}
                        </Link>
                    </div>
                ),
            },
        ],
        [iconSpacingClass, locale],
    );

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.platform_portfolio"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('dashboard.portfolio_projects.page_title')}</h4>}
        >
            <Head title={__('dashboard.portfolio_projects.page_title')} />

            <div className="card card-primary card-outline mb-3">
                <div className="card-body py-2">
                    <div className="d-flex flex-wrap align-items-center justify-content-between">
                        <div className="d-flex flex-wrap align-items-center my-1">
                            <StatusBadge variant="primary" pill>
                                {__('dashboard.nav.platform_portfolio')}
                            </StatusBadge>
                            <Link href={route('platform.dashboard')} className="btn btn-outline-secondary btn-sm mx-2">
                                <i className={`${isRtl ? 'fas fa-arrow-right' : 'fas fa-arrow-left'} ${iconSpacingClass}`} />
                                {__('dashboard.actions.back')}
                            </Link>
                        </div>
                        <div className="d-flex flex-wrap align-items-center my-1">
                            <div className="d-flex align-items-center border rounded px-3 py-2 mx-1 bg-light">
                                <i className={`fas fa-briefcase text-primary ${iconSpacingClass}`} />
                                <span className="text-muted small mx-1">{__('dashboard.portfolio_projects.total_projects')}</span>
                                <strong className="text-dark" dir="ltr">
                                    {totalProjects}
                                </strong>
                            </div>
                            <div className="d-flex align-items-center border rounded px-3 py-2 mx-1 bg-light">
                                <i className={`fas fa-building text-info ${iconSpacingClass}`} />
                                <span className="text-muted small mx-1">{__('dashboard.portfolio_projects.company_filter')}</span>
                                <strong className="text-dark text-truncate" style={{ maxWidth: 180 }}>
                                    {selectedCompany ? companyDisplayName(selectedCompany) : __('dashboard.portfolio_projects.all_companies')}
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className={hasActiveFilters ? 'card card-outline card-primary' : 'card card-outline card-primary collapsed-card'}>
                <div className="card-header" data-card-widget="collapse" style={{ cursor: 'pointer' }}>
                    <h3 className="card-title">
                        <span>
                            <i className="fas fa-filter" />
                        </span>
                        <span className="mx-1">{__('dashboard.portfolio_projects.filters_title')}</span>
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
                        <div className="col-sm-12 col-lg-6 mb-3 mb-lg-0">
                            <input
                                className="form-control"
                                name="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder={__('dashboard.portfolio_projects.search_placeholder')}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            />
                        </div>

                        <div className="col-sm-12 col-md-6 col-lg-3 mb-3 mb-lg-0">
                            <select
                                className="custom-select"
                                name="company_id"
                                value={companyId}
                                onChange={(e) => setCompanyId(e.target.value)}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            >
                                <option value="">{__('dashboard.portfolio_projects.all_companies')}</option>
                                {companies.map((company) => (
                                    <option key={company.id} value={company.id}>
                                        {companyDisplayName(company)}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="col-sm-12 col-md-6 col-lg-3 text-lg-end">
                            <button type="submit" className="btn btn-primary px-4" disabled={isFiltering}>
                                <i className={`fas fa-filter ${iconSpacingClass}`} />
                                {__('dashboard.actions.filter')}
                            </button>
                            <button
                                type="button"
                                className="btn btn-outline-secondary px-4 mx-2"
                                onClick={resetFilters}
                                disabled={isFiltering}
                            >
                                <i className={`fas fa-undo ${iconSpacingClass}`} />
                                {__('dashboard.actions.reset')}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <TableCard title={__('dashboard.portfolio_projects.list_title')} bodyPadding={false}>
                <DataTable<Row>
                    columns={columns}
                    rows={projects.data}
                    rowKey={(project) => project.id}
                    emptyTitle={__('dashboard.portfolio_projects.empty')}
                    emptyDescription={__('dashboard.portfolio_projects.empty_description')}
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
        </PlatformAdminLayout>
    );
}
