import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import DataTable from '@/Components/ui/DataTable';
import Pagination from '@/Components/ui/Pagination';
import TableCard from '@/Components/ui/TableCard';
import type { TableToolbarColumn } from '@/Components/ui/TableToolbar';
import TableToolbar from '@/Components/ui/TableToolbar';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

type Address = { id: number; full_address: string | null; lat: string; lng: string } | null;

type CustomerRow = {
    id: number;
    date_of_birth: string | null;
    gender: string | null;
    user: { name: string; email: string | null; phone: string | null };
    default_address: Address;
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

export default function PlatformCustomersIndex({
    customers,
    filters,
}: {
    customers: Paginator<CustomerRow>;
    filters: { search: string; per_page?: number };
}) {
    const { direction } = usePage<PageProps>().props;
    const isRtl = direction === 'rtl';

    const [search, setSearch] = useState(filters.search ?? '');
    const [perPage, setPerPage] = useState<number>(Number(filters.per_page ?? 10) || 10);
    const [isFiltering, setIsFiltering] = useState(false);
    const [visibleColumnIds, setVisibleColumnIds] = useState<string[]>([]);
    const hasActiveFilters = Boolean((filters.search ?? '').trim());

    const applyFilters = () => {
        setIsFiltering(true);
        router.get(
            route('platform.customers.index'),
            {
                search: search || undefined,
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
        setPerPage(10);
        setIsFiltering(true);
        router.get(
            route('platform.customers.index'),
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
        TableToolbarColumn<CustomerRow> & {
            key: string;
            header: React.ReactNode;
            className?: string;
            headerClassName?: string;
        }
    > = useMemo(
        () => [
            {
                id: 'name',
                key: 'name',
                label: __('tenant.customers.name'),
                header: __('tenant.customers.name'),
                render: (r) => <strong>{r.user.name}</strong>,
                searchText: (r) => r.user.name ?? '',
            },
            {
                id: 'contact',
                key: 'contact',
                label: __('tenant.customers.contact'),
                header: __('tenant.customers.contact'),
                render: (r) => (
                    <div dir="ltr">
                        <div>{r.user.phone || '—'}</div>
                        <div className="text-muted">{r.user.email || '—'}</div>
                    </div>
                ),
                searchText: (r) => `${r.user.phone ?? ''} ${r.user.email ?? ''}`.trim(),
            },
            {
                id: 'default_address',
                key: 'default_address',
                label: __('tenant.customers.defaultAddress'),
                header: __('tenant.customers.defaultAddress'),
                render: (r) => r.default_address?.full_address ?? '—',
                searchText: (r) => r.default_address?.full_address ?? '',
            },
            {
                id: 'actions',
                key: 'actions',
                label: __('tenant.customers.actions'),
                header: <span className="d-block text-end">{__('tenant.customers.actions')}</span>,
                className: 'text-end',
                render: (r) => (
                    <Link href={route('platform.customers.show', r.id)} className="btn btn-outline-primary btn-sm">
                        {__('tenant.customers.view')}
                    </Link>
                ),
            },
        ],
        [__],
    );

    const resolvedVisibleColumnIds = visibleColumnIds.length > 0 ? visibleColumnIds : tableColumns.map((c) => c.id);
    const visibleTableColumns = tableColumns.filter((c) => resolvedVisibleColumnIds.includes(c.id));

    return (
        <PlatformAdminLayout
            header={<h4 className="m-0 font-weight-bold text-dark">{__('tenant.customers.title')}</h4>}
        >
            <Head title={__('tenant.customers.title')} />

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
                        <div className="col-sm-12 col-md-8 col-lg-8">
                            <input
                                className="form-control"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder={__('tenant.customers.searchPlaceholder')}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            />
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

            <TableCard title={__('tenant.customers.list')} bodyPadding={false}>
                <div className="p-3 border-bottom">
                    <TableToolbar<CustomerRow>
                        rows={customers.data}
                        columns={tableColumns}
                        showSearch={false}
                        exportFilename="platform-customers"
                        persistKey="platform.customers.index"
                        onVisibleColumnIdsChange={(ids) => setVisibleColumnIds(ids)}
                        showPerPage
                        perPage={perPage}
                        perPageOptions={[10, 25, 50, 100]}
                        onPerPageChange={(value) => {
                            const next = Number(value) || 10;
                            setPerPage(next);
                            setIsFiltering(true);
                            router.get(
                                route('platform.customers.index'),
                                {
                                    search: search || undefined,
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

                <DataTable<CustomerRow>
                    columns={visibleTableColumns}
                    rows={customers.data}
                    rowKey={(r) => r.id}
                    emptyTitle={__('tenant.customers.empty')}
                />
                <div className="px-3 pb-3">
                    <Pagination
                        links={customers.links}
                        data={{
                            current_page: customers.current_page,
                            last_page: customers.last_page,
                            prev_page_url: customers.prev_page_url,
                            next_page_url: customers.next_page_url,
                            from: customers.from,
                            to: customers.to,
                            total: customers.total,
                        }}
                    />
                </div>
            </TableCard>
        </PlatformAdminLayout>
    );
}
