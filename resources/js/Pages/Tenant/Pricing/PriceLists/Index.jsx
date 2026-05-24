import DataTable from '@/Components/ui/DataTable';
import Pagination from '@/Components/ui/Pagination';
import StatusBadge from '@/Components/ui/StatusBadge';
import TableCard from '@/Components/ui/TableCard';
import TableToolbar from '@/Components/ui/TableToolbar';
import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useCallback, useMemo, useState } from 'react';

function rawValue(value) {
    if (value == null || value === '') {
        return null;
    }

    if (typeof value === 'object') {
        return value.value ?? value.label ?? null;
    }

    return value;
}

export default function TenantPriceListsIndex({ priceLists, filters }) {
    const { direction } = usePage().props;
    const isRtl = direction === 'rtl';

    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');
    const [perPage, setPerPage] = useState(Number(filters.per_page ?? 10) || 10);
    const [isFiltering, setIsFiltering] = useState(false);
    const [visibleColumnIds, setVisibleColumnIds] = useState([]);

    const hasActiveFilters = Boolean((filters.search ?? '').trim() || (filters.status ?? 'all') !== 'all');

    const requestParams = useCallback(
        (nextPerPage = perPage) => ({
            search: search || undefined,
            status: status || 'all',
            per_page: nextPerPage || undefined,
        }),
        [perPage, search, status],
    );

    const applyFilters = () => {
        setIsFiltering(true);
        router.get(route('price-lists.index'), requestParams(), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => setIsFiltering(false),
        });
    };

    const resetFilters = () => {
        setSearch('');
        setStatus('all');
        setPerPage(10);
        setIsFiltering(true);
        router.get(route('price-lists.index'), {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => setIsFiltering(false),
        });
    };

    const destroy = useCallback((id) => {
        if (!confirm(__('tenant.confirm_delete'))) {
            return;
        }

        router.delete(route('price-lists.destroy', id), { preserveScroll: true });
    }, []);

    const displayDiscount = useCallback((type, value) => {
        const rawType = rawValue(type);

        if (!rawType || value == null || value === '') {
            return __('tenant.no_discount');
        }

        return `${__(`tenant.discount_type_${rawType}`)}: ${value}`;
    }, []);

    const tableColumns = useMemo(
        () => [
            {
                id: 'name',
                key: 'name',
                label: __('tenant.name'),
                header: __('tenant.name'),
                render: (row) => <strong>{row.name}</strong>,
                searchText: (row) => row.name ?? '',
            },
            {
                id: 'description',
                key: 'description',
                label: __('tenant.description'),
                header: __('tenant.description'),
                render: (row) => row.description ?? '—',
                searchText: (row) => row.description ?? '',
            },
            {
                id: 'discount',
                key: 'discount',
                label: __('tenant.price_list_discount'),
                header: __('tenant.price_list_discount'),
                render: (row) => <span dir="ltr">{displayDiscount(row.discount_type, row.discount_value)}</span>,
                searchText: (row) => displayDiscount(row.discount_type, row.discount_value),
            },
            {
                id: 'categories',
                key: 'categories',
                label: __('tenant.price_list_categories'),
                header: __('tenant.price_list_categories'),
                render: (row) => {
                    const names = (row.service_categories ?? []).map((category) => category.name_ar).filter(Boolean);

                    return names.length > 0 ? names.join(', ') : '—';
                },
                searchText: (row) => (row.service_categories ?? []).map((category) => category.name_ar ?? '').join(' '),
            },
            {
                id: 'default',
                key: 'default',
                label: __('tenant.is_default_list'),
                header: __('tenant.is_default_list'),
                render: (row) =>
                    row.is_default ? (
                        <StatusBadge variant="info" pill>
                            {__('tenant.is_default_list')}
                        </StatusBadge>
                    ) : (
                        <span className="text-muted">—</span>
                    ),
            },
            {
                id: 'starts_at',
                key: 'starts_at',
                label: __('tenant.date_start'),
                header: __('tenant.date_start'),
                render: (row) => <span dir="ltr">{row.starts_at ?? '—'}</span>,
            },
            {
                id: 'ends_at',
                key: 'ends_at',
                label: __('tenant.date_end'),
                header: __('tenant.date_end'),
                render: (row) => <span dir="ltr">{row.ends_at ?? '—'}</span>,
            },
            {
                id: 'status',
                key: 'status',
                label: __('tenant.status'),
                header: __('tenant.status'),
                render: (row) => (
                    <StatusBadge variant={row.is_active ? 'success' : 'secondary'} pill>
                        {row.is_active ? __('tenant.active') : __('tenant.no_active')}
                    </StatusBadge>
                ),
                searchText: (row) => (row.is_active ? __('tenant.active') : __('tenant.no_active')),
            },
            {
                id: 'actions',
                key: 'actions',
                label: __('tenant.action'),
                header: <span className="d-block text-end">{__('tenant.action')}</span>,
                className: 'text-end',
                render: (row) => (
                    <div className="btn-group">
                        <Link href={route('price-lists.edit', row.id)} className="btn btn-outline-primary btn-sm">
                            {__('tenant.edit')}
                        </Link>
                        <button type="button" className="btn btn-outline-danger btn-sm" onClick={() => destroy(row.id)}>
                            {__('tenant.delete')}
                        </button>
                    </div>
                ),
            },
        ],
        [destroy, displayDiscount],
    );

    const resolvedVisibleColumnIds = visibleColumnIds.length > 0 ? visibleColumnIds : tableColumns.map((column) => column.id);
    const visibleTableColumns = tableColumns.filter((column) => resolvedVisibleColumnIds.includes(column.id));

    return (
        <CompanyDashboardLayout breadcrumbKey="dashboard.nav.company_price_lists">
            <Head title={__('tenant.price_lists')} />

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
                <div className="card-body" style={hasActiveFilters ? undefined : { display: 'none' }}>
                    <form
                        className="row"
                        onSubmit={(e) => {
                            e.preventDefault();
                            applyFilters();
                        }}
                    >
                        <div className="col-sm-12 col-md-6 col-lg-6">
                            <input
                                type="search"
                                className="form-control"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder={__('tenant.search_price_lists_placeholder')}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            />
                        </div>
                        <div className="col-sm-12 col-md-3 col-lg-3">
                            <select
                                className="custom-select"
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            >
                                <option value="all">{__('tenant.all')}</option>
                                <option value="active">{__('tenant.active')}</option>
                                <option value="inactive">{__('tenant.no_active')}</option>
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
                title={__('tenant.price_lists')}
                actions={
                    <Link href={route('price-lists.create')} className="btn btn-success btn-sm">
                        <i className={isRtl ? 'fas fa-plus ml-1' : 'fas fa-plus mr-1'} />
                        {__('tenant.add_price_list')}
                    </Link>
                }
                actionsVariant="card-tools"
                bodyPadding={false}
            >
                <div className="p-3 border-bottom">
                    <TableToolbar
                        rows={priceLists.data}
                        columns={tableColumns}
                        showSearch={false}
                        exportFilename="tenant-price-lists"
                        persistKey="tenant.price-lists.index"
                        onVisibleColumnIdsChange={(ids) => setVisibleColumnIds(ids)}
                        showPerPage
                        perPage={perPage}
                        perPageOptions={[10, 25, 50, 100]}
                        onPerPageChange={(value) => {
                            const next = Number(value) || 10;
                            setPerPage(next);
                            setIsFiltering(true);
                            router.get(route('price-lists.index'), requestParams(next), {
                                preserveState: true,
                                preserveScroll: true,
                                replace: true,
                                onFinish: () => setIsFiltering(false),
                            });
                        }}
                    />
                </div>

                <DataTable
                    columns={visibleTableColumns}
                    rows={priceLists.data}
                    rowKey={(row) => row.id}
                    emptyTitle={__('tenant.price_lists_empty')}
                />
                <div className="px-3 pb-3">
                    <Pagination
                        links={priceLists.links}
                        data={{
                            current_page: priceLists.current_page,
                            last_page: priceLists.last_page,
                            prev_page_url: priceLists.prev_page_url,
                            next_page_url: priceLists.next_page_url,
                            from: priceLists.from,
                            to: priceLists.to,
                            total: priceLists.total,
                        }}
                    />
                </div>
            </TableCard>
        </CompanyDashboardLayout>
    );
}
