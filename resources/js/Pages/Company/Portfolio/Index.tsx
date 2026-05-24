import DataTable from '@/Components/ui/DataTable';
import Pagination from '@/Components/ui/Pagination';
import StatusBadge from '@/Components/ui/StatusBadge';
import TableCard from '@/Components/ui/TableCard';
import type { TableToolbarColumn } from '@/Components/ui/TableToolbar';
import TableToolbar from '@/Components/ui/TableToolbar';
import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useCallback, useMemo, useState } from 'react';

type Category = { id: number; name_ar: string; name_en: string | null };

type Row = {
    id: number;
    title_ar: string;
    title_en: string | null;
    is_featured: boolean;
    is_active: boolean;
    review_status: string | null;
    rejection_reason: string | null;
    created_at: string | null;
    thumbnail_url: string | null;
    images_count: number;
    service_category: Category | null;
    technicians: { id: number; user: { name: string | null } }[];
    technician: { id: number; user: { name: string | null } } | null;
    can: {
        update: boolean;
        delete: boolean;
        upload_images: boolean;
    };
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

function localizedTitle(project: Row, locale: string): string {
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

function technicianNames(project: Row): string {
    const names = (project.technicians ?? [])
        .map((technician) => technician.user.name)
        .filter(Boolean);

    return names.length > 0 ? names.join(', ') : __('dashboard.portfolio_projects.no_technician');
}

export default function CompanyPortfolioIndex({
    projects,
    filters,
    categories,
}: {
    projects: Paginated<Row>;
    filters: { search: string; review_status: string; service_category_id?: string | number | null; per_page?: number };
    categories: Category[];
}) {
    const page = usePage<PageProps>().props;
    const { locale, direction } = page;
    const isRtl = direction === 'rtl';
    const statusLabels = page.enumLabels?.portfolio_project_review_status ?? {};
    const iconSpacingClass = isRtl ? 'ms-1' : 'me-1';
    const userPermissions = page.auth?.user?.permissions ?? [];
    const canCreateProject = userPermissions.includes('portfolio.create') || userPermissions.includes('portfolio.manage');

    const [search, setSearch] = useState(filters.search ?? '');
    const [reviewStatus, setReviewStatus] = useState(filters.review_status ?? 'all');
    const [categoryId, setCategoryId] = useState(String(filters.service_category_id ?? ''));
    const [perPage, setPerPage] = useState<number>(Number(filters.per_page ?? 10) || 10);
    const [isFiltering, setIsFiltering] = useState(false);
    const [visibleColumnIds, setVisibleColumnIds] = useState<string[]>([]);

    const hasActiveFilters = Boolean((filters.search ?? '').trim() || String(filters.service_category_id ?? '').trim() || filters.review_status !== 'all');

    const statusBadge = (status: string | null) => {
        const value = (status ?? '').toLowerCase();
        if (value === 'approved') return <StatusBadge variant="success">{statusLabels.approved ?? value}</StatusBadge>;
        if (value === 'pending') return <StatusBadge variant="warning">{statusLabels.pending ?? value}</StatusBadge>;
        if (value === 'rejected') return <StatusBadge variant="danger">{statusLabels.rejected ?? value}</StatusBadge>;
        return <span className="text-muted">—</span>;
    };

    const requestProjects = (params: Record<string, string | number | undefined>) => {
        setIsFiltering(true);
        router.get(route('company.portfolio-projects.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => setIsFiltering(false),
        });
    };

    const applyFilters = () => {
        requestProjects({
            search: search || undefined,
            review_status: reviewStatus !== 'all' ? reviewStatus : undefined,
            service_category_id: categoryId || undefined,
            per_page: perPage,
        });
    };

    const resetFilters = () => {
        setSearch('');
        setReviewStatus('all');
        setCategoryId('');
        setPerPage(10);
        requestProjects({});
    };

    const deleteProject = useCallback((project: Row) => {
        const title = localizedTitle(project, locale);
        if (!confirm(__('tenant.portfolio_projects.remove_project_confirm', { title }))) {
            return;
        }

        router.delete(route('company.portfolio-projects.destroy', project.id), {
            preserveScroll: true,
        });
    }, [locale]);

    const tableColumns: Array<
        TableToolbarColumn<Row> & { key: string; header: React.ReactNode; className?: string; headerClassName?: string }
    > = useMemo(
        () => [
            {
                id: 'project',
                key: 'project',
                label: __('tenant.portfolio_projects.project'),
                header: __('tenant.portfolio_projects.project'),
                render: (project) => (
                    <div className="d-flex align-items-center">
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
                        <div className="mx-2">
                            <div className="font-weight-bold">{localizedTitle(project, locale)}</div>
                            {project.is_featured ? (
                                <div>
                                    <StatusBadge variant="warning">{__('dashboard.portfolio_projects.featured')}</StatusBadge>
                                </div>
                            ) : null}
                        </div>
                    </div>
                ),
            },
            {
                id: 'category',
                key: 'category',
                label: __('tenant.portfolio_projects.category'),
                header: __('tenant.portfolio_projects.category'),
                render: (project) => localizedCategoryName(project.service_category, locale),
            },
            {
                id: 'technician',
                key: 'technician',
                label: __('tenant.portfolio_projects.technicians'),
                header: __('tenant.portfolio_projects.technicians'),
                render: (project) => technicianNames(project),
            },
            {
                id: 'review_status',
                key: 'review_status',
                label: __('tenant.portfolio_projects.review_status'),
                header: __('tenant.portfolio_projects.review_status'),
                render: (project) => (
                    <div>
                        {statusBadge(project.review_status)}
                        {String(project.review_status ?? '').toLowerCase() === 'rejected' && project.rejection_reason ? (
                            <div className="text-muted small mt-1">{project.rejection_reason}</div>
                        ) : null}
                    </div>
                ),
            },
            {
                id: 'images_count',
                key: 'images_count',
                label: __('tenant.portfolio_projects.images'),
                header: __('tenant.portfolio_projects.images'),
                render: (project) => (
                    <span dir="ltr">
                        {__('dashboard.portfolio_projects.photos_count', {
                            count: String(project.images_count),
                        })}
                    </span>
                ),
            },
            {
                id: 'actions',
                key: 'actions',
                label: __('tenant.portfolio_projects.actions'),
                header: <span className="d-block text-end">{__('tenant.portfolio_projects.actions')}</span>,
                className: 'text-end',
                render: (project) => (
                    <div className="btn-group btn-group-sm">
                        {project.can.update || project.can.upload_images ? (
                            <Link href={route('company.portfolio-projects.edit', project.id)} className="btn btn-outline-primary">
                                <i className={`fas fa-pen ${iconSpacingClass}`} />
                                {project.can.update ? __('dashboard.actions.edit') : __('tenant.portfolio_projects.manage_images')}
                            </Link>
                        ) : null}
                        {project.can.delete ? (
                            <button type="button" className="btn btn-outline-danger" onClick={() => deleteProject(project)}>
                                <i className={`fas fa-trash ${iconSpacingClass}`} />
                                {__('dashboard.actions.delete')}
                            </button>
                        ) : null}
                    </div>
                ),
            },
        ],
        [deleteProject, iconSpacingClass, locale, statusLabels],
    );

    const resolvedVisibleColumnIds = visibleColumnIds.length > 0 ? visibleColumnIds : tableColumns.map((column) => column.id);
    const visibleTableColumns = tableColumns.filter((column) => resolvedVisibleColumnIds.includes(column.id));

    return (
        <CompanyDashboardLayout
            breadcrumbKey="dashboard.nav.company_portfolio"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('tenant.portfolio_projects.title')}</h4>}
        >
            <Head title={__('tenant.portfolio_projects.title')} />

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
                        onSubmit={(event) => {
                            event.preventDefault();
                            applyFilters();
                        }}
                    >
                        <div className="col-sm-12 col-md-5">
                            <input
                                className="form-control"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder={__('tenant.portfolio_projects.search_placeholder')}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            />
                        </div>
                        <div className="col-sm-12 col-md-3 mt-3 mt-md-0">
                            <select className="custom-select" value={categoryId} onChange={(event) => setCategoryId(event.target.value)}>
                                <option value="">{__('tenant.all_categories')}</option>
                                {categories.map((category) => (
                                    <option key={category.id} value={category.id}>
                                        {localizedCategoryName(category, locale)}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="col-sm-12 col-md-4 mt-3 mt-md-0">
                            <select className="custom-select" value={reviewStatus} onChange={(event) => setReviewStatus(event.target.value)}>
                                <option value="all">{__('tenant.portfolio_projects.all_review_statuses')}</option>
                                <option value="pending">{statusLabels.pending ?? 'pending'}</option>
                                <option value="approved">{statusLabels.approved ?? 'approved'}</option>
                                <option value="rejected">{statusLabels.rejected ?? 'rejected'}</option>
                            </select>
                        </div>
                        <div className="col-sm-12 mt-3" style={{ textAlign: isRtl ? 'right' : 'left' }}>
                            <button type="submit" className="btn btn-primary px-4" disabled={isFiltering}>
                                <i className={`fas fa-filter ${iconSpacingClass}`} />
                                {__('tenant.filter')}
                            </button>
                            <button type="button" className="btn btn-danger px-4 mx-2" onClick={resetFilters} disabled={isFiltering}>
                                <i className={`fas fa-undo ${iconSpacingClass}`} />
                                {__('tenant.reset')}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <TableCard
                title={__('tenant.portfolio_projects.list')}
                actions={
                    canCreateProject ? <Link href={route('company.portfolio-projects.create')} className="btn btn-success btn-sm">
                        <i className={`fas fa-plus ${iconSpacingClass}`} />
                        {__('tenant.portfolio_projects.create')}
                    </Link> : null
                }
                actionsVariant="card-tools"
                bodyPadding={false}
            >
                <div className="p-3 border-bottom">
                    <TableToolbar<Row>
                        rows={projects.data}
                        columns={tableColumns}
                        showSearch={false}
                        exportFilename="company-portfolio-projects"
                        persistKey="company.portfolio-projects.index.v1"
                        onVisibleColumnIdsChange={(ids) => setVisibleColumnIds(ids)}
                        showPerPage
                        perPage={perPage}
                        perPageOptions={[10, 25, 50, 100]}
                        onPerPageChange={(value) => {
                            const next = Number(value) || 10;
                            setPerPage(next);
                            requestProjects({
                                search: search || undefined,
                                review_status: reviewStatus !== 'all' ? reviewStatus : undefined,
                                service_category_id: categoryId || undefined,
                                per_page: next,
                            });
                        }}
                    />
                </div>

                <DataTable<Row>
                    columns={visibleTableColumns}
                    rows={projects.data}
                    rowKey={(project) => project.id}
                    emptyTitle={__('tenant.portfolio_projects.empty')}
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
        </CompanyDashboardLayout>
    );
}
