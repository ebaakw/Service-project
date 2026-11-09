import Pagination from '@/Components/ui/Pagination';
import SortableDataTable from '@/Components/ui/SortableDataTable';
import StatusBadge from '@/Components/ui/StatusBadge';
import TableCard from '@/Components/ui/TableCard';
import type { TableToolbarColumn } from '@/Components/ui/TableToolbar';
import TableToolbar from '@/Components/ui/TableToolbar';
import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';

type Category = { id: number; name_ar: string; slug: string };
type ProblemOption = { id: number; title_ar: string };
type FieldRow = {
    id: number;
    label_ar: string;
    label_en: string | null;
    field_type: string | { value: string };
    is_active: boolean;
    sort_order: number;
    service_problem_id: number | null;
    problem?: { id: number; title_ar: string } | null;
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

export default function PlatformIntakeFieldsIndex({
    category,
    problems,
    fields,
    filters,
}: {
    category: Category;
    problems: ProblemOption[];
    fields: Paginator<FieldRow>;
    filters: { search: string; status: string; problem_id: string | null | undefined; per_page?: number };
}) {
    const { direction } = usePage<PageProps>().props;
    const isRtl = direction === 'rtl';

    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');
    const [problemId, setProblemId] = useState(filters.problem_id ?? '');
    const [perPage, setPerPage] = useState<number>(Number(filters.per_page ?? 10) || 10);
    const [isFiltering, setIsFiltering] = useState(false);
    const [visibleColumnIds, setVisibleColumnIds] = useState<string[]>([]);
    const [rows, setRows] = useState<FieldRow[]>(fields.data);
    const [isReordering, setIsReordering] = useState(false);

    useEffect(() => {
        setRows(fields.data);
    }, [fields.data]);

    const hasActiveFilters = Boolean(
        (filters.search ?? '').trim()
            || ((filters.status ?? 'all') !== 'all' && (filters.status ?? '') !== '')
            || (filters.problem_id ?? ''),
    );

    const handleReorder = useCallback(
        (orderedIds: Array<string | number>) => {
            if (isReordering) {
                return;
            }

            const idOrder = orderedIds.map((id) => Number(id));
            const byId = new Map(rows.map((row) => [row.id, row]));
            const nextRows = idOrder
                .map((id) => byId.get(id))
                .filter((row): row is FieldRow => row !== undefined);

            if (nextRows.length !== rows.length) {
                return;
            }

            const previousOrders = rows.map((row) => row.sort_order).sort((a, b) => a - b);
            const withUpdatedOrders = nextRows.map((row, index) => ({
                ...row,
                sort_order: previousOrders[index] ?? row.sort_order,
            }));

            setRows(withUpdatedOrders);
            setIsReordering(true);

            router.patch(
                route('platform.service-categories.intake-fields.reorder', category.id),
                { ids: idOrder },
                {
                    preserveScroll: true,
                    onFinish: () => setIsReordering(false),
                    onError: () => setRows(fields.data),
                },
            );
        },
        [category.id, fields.data, isReordering, rows],
    );

    const applyFilters = () => {
        setIsFiltering(true);
        router.get(
            route('platform.service-categories.intake-fields.index', category.id),
            {
                search: search || undefined,
                status: status === 'all' ? undefined : status,
                problem_id: problemId || undefined,
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
        setProblemId('');
        setPerPage(10);
        setIsFiltering(true);
        router.get(
            route('platform.service-categories.intake-fields.index', category.id),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => setIsFiltering(false),
            },
        );
    };

    const destroy = useCallback(
        (id: number) => {
            if (!confirm(__('dashboard.service_intake_fields.delete_confirm'))) {
                return;
            }

            router.delete(route('platform.service-categories.intake-fields.destroy', [category.id, id]), { preserveScroll: true });
        },
        [category.id],
    );

    const fieldTypeText = useCallback((fieldType: FieldRow['field_type']) => {
        const value =
            typeof fieldType === 'object' && fieldType && 'value' in fieldType
                ? String((fieldType as { value: string }).value)
                : String(fieldType);

        const translated = __(`dashboard.service_intake_fields.field_types.${value}`);
        return translated === `dashboard.service_intake_fields.field_types.${value}` ? value : translated;
    }, []);

    const tableColumns: Array<
        TableToolbarColumn<FieldRow> & { key: string; header: React.ReactNode; className?: string; headerClassName?: string }
    > = useMemo(
        () => [
            {
                id: 'label_ar',
                key: 'label_ar',
                label: __('dashboard.service_intake_fields.col_label_ar'),
                header: __('dashboard.service_intake_fields.col_label_ar'),
                render: (row) => <strong>{row.label_ar}</strong>,
                searchText: (row) => row.label_ar ?? '',
            },
            {
                id: 'field_type',
                key: 'field_type',
                label: __('dashboard.service_intake_fields.col_type'),
                header: __('dashboard.service_intake_fields.col_type'),
                render: (row) => <span>{fieldTypeText(row.field_type)}</span>,
                searchText: (row) => fieldTypeText(row.field_type),
            },
            {
                id: 'problem',
                key: 'problem',
                label: __('dashboard.service_intake_fields.col_problem'),
                header: __('dashboard.service_intake_fields.col_problem'),
                render: (row) => <span>{row.problem?.title_ar ?? __('dashboard.service_intake_fields.category_only')}</span>,
                searchText: (row) => row.problem?.title_ar ?? '',
            },
            {
                id: 'sort_order',
                key: 'sort_order',
                label: __('dashboard.service_intake_fields.col_sort'),
                header: __('dashboard.service_intake_fields.col_sort'),
                render: (row) => <span dir="ltr">{row.sort_order}</span>,
                searchText: (row) => String(row.sort_order),
            },
            {
                id: 'is_active',
                key: 'is_active',
                label: __('dashboard.service_intake_fields.col_active'),
                header: __('dashboard.service_intake_fields.col_active'),
                render: (row) => (
                    <StatusBadge variant={row.is_active ? 'success' : 'secondary'} pill>
                        {row.is_active ? __('dashboard.status.active') : __('dashboard.status.inactive')}
                    </StatusBadge>
                ),
                searchText: (row) => (row.is_active ? __('dashboard.status.active') : __('dashboard.status.inactive')),
            },
            {
                id: 'actions',
                key: 'actions',
                label: __('dashboard.service_intake_fields.col_actions'),
                header: <span className="d-block text-end">{__('dashboard.service_intake_fields.col_actions')}</span>,
                className: 'text-end',
                render: (row) => (
                    <div className="btn-group">
                        <Link
                            href={route('platform.service-categories.intake-fields.edit', [category.id, row.id])}
                            className="btn btn-outline-primary btn-sm"
                        >
                            {__('dashboard.actions.edit')}
                        </Link>
                        <button type="button" className="btn btn-outline-danger btn-sm" onClick={() => destroy(row.id)}>
                            {__('dashboard.actions.delete')}
                        </button>
                    </div>
                ),
            },
        ],
        [category.id, destroy, fieldTypeText],
    );

    const resolvedVisibleColumnIds = visibleColumnIds.length > 0 ? visibleColumnIds : tableColumns.map((c) => c.id);
    const visibleTableColumns = tableColumns.filter((c) => resolvedVisibleColumnIds.includes(c.id));

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.global_service_catalog"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('dashboard.service_intake_fields.page_title')}</h4>}
        >
            <Head title={__('dashboard.service_intake_fields.page_title')} />

            <div className="mb-3">
                <Link href={route('platform.service-categories.index')} className="btn btn-outline-secondary btn-sm">
                    <i className={isRtl ? 'fas fa-arrow-right ml-1' : 'fas fa-arrow-left mr-1'} />
                    {__('dashboard.actions.back')}
                </Link>
            </div>

            <div className="card card-outline card-info">
                <div className="card-body py-2">
                    <span className="text-muted">{__('dashboard.service_intake_fields.category_label')}</span>
                    <strong className="mx-1">{category.name_ar}</strong>
                    <span dir="ltr" className="text-muted">
                        ({category.slug})
                    </span>
                </div>
            </div>

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
                        onSubmit={(e) => {
                            e.preventDefault();
                            applyFilters();
                        }}
                    >
                        <div className="col-sm-12 col-md-5 col-lg-5">
                            <input
                                className="form-control"
                                name="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder={__('dashboard.service_intake_fields.search_placeholder')}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            />
                        </div>

                        <div className="col-sm-6 col-md-3 col-lg-3">
                            <select
                                className="custom-select"
                                name="status"
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            >
                                <option value="all">{__('dashboard.service_categories.status_all')}</option>
                                <option value="active">{__('dashboard.status.active')}</option>
                                <option value="inactive">{__('dashboard.status.inactive')}</option>
                            </select>
                        </div>

                        <div className="col-sm-6 col-md-4 col-lg-4">
                            <select
                                className="custom-select"
                                name="problem_id"
                                value={problemId}
                                onChange={(e) => setProblemId(e.target.value)}
                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                            >
                                <option value="">{__('dashboard.service_intake_fields.all_problems')}</option>
                                {problems.map((problem) => (
                                    <option key={problem.id} value={problem.id}>
                                        {problem.title_ar}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="col-sm-12 mt-3" style={{ textAlign: isRtl ? 'right' : 'left' }}>
                            <button type="submit" className="btn btn-primary px-4" disabled={isFiltering}>
                                <i className={isRtl ? 'fas fa-filter ml-1' : 'fas fa-filter mr-1'} />
                                {__('tenant.filter')}
                            </button>
                            <button
                                type="button"
                                className="btn btn-danger px-4 mx-2"
                                onClick={resetFilters}
                                disabled={isFiltering}
                            >
                                <i className={isRtl ? 'fas fa-undo ml-1' : 'fas fa-undo mr-1'} />
                                {__('tenant.reset')}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <TableCard
                title={__('dashboard.service_intake_fields.list_title')}
                actions={
                    <Link href={route('platform.service-categories.intake-fields.create', category.id)} className="btn btn-success btn-sm">
                        <i className={isRtl ? 'fas fa-plus ml-1' : 'fas fa-plus mr-1'} />
                        {__('dashboard.service_intake_fields.add_field')}
                    </Link>
                }
                actionsVariant="card-tools"
                bodyPadding={false}
            >
                <div className="p-3 border-bottom">
                    <TableToolbar<FieldRow>
                        rows={rows}
                        columns={tableColumns}
                        showSearch={false}
                        exportFilename="platform-service-intake-fields"
                        persistKey="platform.service_intake_fields.index"
                        onVisibleColumnIdsChange={(ids) => setVisibleColumnIds(ids)}
                        showPerPage
                        perPage={perPage}
                        perPageOptions={[10, 25, 50, 100]}
                        onPerPageChange={(value) => {
                            const next = Number(value) || 10;
                            setPerPage(next);
                            setIsFiltering(true);
                            router.get(
                                route('platform.service-categories.intake-fields.index', category.id),
                                {
                                    search: search || undefined,
                                    status: status === 'all' ? undefined : status,
                                    problem_id: problemId || undefined,
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

                <SortableDataTable<FieldRow>
                    columns={visibleTableColumns}
                    rows={rows}
                    rowKey={(row) => row.id}
                    emptyTitle={__('dashboard.service_intake_fields.empty')}
                    dragHandleLabel={__('dashboard.service_intake_fields.drag_handle')}
                    disabled={isReordering}
                    onReorder={handleReorder}
                />
                <div className="px-3 pb-3">
                    <Pagination
                        links={fields.links}
                        data={{
                            current_page: fields.current_page,
                            last_page: fields.last_page,
                            prev_page_url: fields.prev_page_url,
                            next_page_url: fields.next_page_url,
                            from: fields.from,
                            to: fields.to,
                            total: fields.total,
                        }}
                    />
                </div>
            </TableCard>
        </PlatformAdminLayout>
    );
}
