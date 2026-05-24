import DataTable from '@/Components/ui/DataTable';
import Pagination from '@/Components/ui/Pagination';
import StatusBadge from '@/Components/ui/StatusBadge';
import TableCard from '@/Components/ui/TableCard';
import TableToolbar from '@/Components/ui/TableToolbar';
import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useCallback, useMemo, useState } from 'react';
import * as XLSX from 'xlsx';

const PRICE_TYPES = ['fixed', 'starting_from', 'hourly', 'custom_quote'];
const DISCOUNT_TYPES = ['percentage', 'fixed_amount'];
const TEMPLATE_COLUMNS = [
    'id',
    'service_category_id',
    'name_ar',
    'name_en',
    'description_ar',
    'description_en',
    'price_type',
    'price',
    'minimum_price',
    'discount_type',
    'discount_value',
    'warranty_days',
    'warranty_description_ar',
    'warranty_description_en',
    'sort_order',
    'is_active',
];

function rawValue(value) {
    if (value == null || value === '') {
        return null;
    }

    if (typeof value === 'object') {
        return value.value ?? value.label ?? null;
    }

    return value;
}

export default function TenantPriceItemsIndex({ priceItems, priceLists, filters }) {
    const { direction, errors = {} } = usePage().props;
    const isRtl = direction === 'rtl';

    const [activeTab, setActiveTab] = useState('items');
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');
    const [priceListId, setPriceListId] = useState(filters.price_list_id ?? '');
    const [importPriceListId, setImportPriceListId] = useState(filters.price_list_id || priceLists?.[0]?.id || '');
    const [perPage, setPerPage] = useState(Number(filters.per_page ?? 10) || 10);
    const [isFiltering, setIsFiltering] = useState(false);
    const [isImporting, setIsImporting] = useState(false);
    const [visibleColumnIds, setVisibleColumnIds] = useState([]);

    const displayPriceType = useCallback((value) => {
        const raw = rawValue(value);

        return raw ? __(`tenant.price_type_${raw}`) : '—';
    }, []);

    const displayDiscount = useCallback((type, value) => {
        const raw = rawValue(type);

        if (!raw || value == null || value === '') {
            return __('tenant.no_discount');
        }

        return `${__(`tenant.discount_type_${raw}`)}: ${value}`;
    }, []);

    const selectedImportList = useMemo(
        () => priceLists.find((priceList) => Number(priceList.id) === Number(importPriceListId)),
        [importPriceListId, priceLists],
    );

    const importCategories = selectedImportList?.service_categories ?? [];
    const importErrors = Object.entries(errors).filter(
        ([key]) => key === 'rows' || key.startsWith('rows.') || key === 'company_price_list_id',
    );

    const downloadTemplate = () => {
        const workbook = XLSX.utils.book_new();
        const rowsSheet = XLSX.utils.aoa_to_sheet([TEMPLATE_COLUMNS]);
        const categoryRows = importCategories.map((category) => ({
            service_category_id: category.id,
            name_ar: category.name_ar,
            name_en: category.name_en ?? '',
        }));
        const columnHeader = __('tenant.excel_column');
        const requirementHeader = __('tenant.excel_requirement');
        const noteHeader = __('tenant.excel_note');
        const acceptedValuesHeader = __('tenant.excel_accepted_values');
        const referenceRow = (column, requirement, note, acceptedValues = '') => ({
            [columnHeader]: column,
            [requirementHeader]: requirement,
            [noteHeader]: note,
            [acceptedValuesHeader]: acceptedValues,
        });
        const columnReference = [
            referenceRow('id', __('tenant.excel_optional'), __('tenant.price_items_template_id_note')),
            referenceRow(
                'service_category_id',
                __('tenant.excel_required'),
                __('tenant.price_items_template_category_note'),
                importCategories.map((category) => category.id).join(', '),
            ),
            referenceRow('name_ar', __('tenant.excel_required'), __('tenant.price_items_template_name_ar_note')),
            referenceRow('name_en', __('tenant.excel_required'), __('tenant.price_items_template_name_en_note')),
            referenceRow('description_ar', __('tenant.excel_optional'), __('tenant.price_items_template_optional_text_note')),
            referenceRow('description_en', __('tenant.excel_optional'), __('tenant.price_items_template_optional_text_note')),
            referenceRow('price_type', __('tenant.excel_required'), __('tenant.price_items_template_price_type_note'), PRICE_TYPES.join(', ')),
            referenceRow('price', __('tenant.excel_optional'), __('tenant.price_items_template_money_note')),
            referenceRow('minimum_price', __('tenant.excel_optional'), __('tenant.price_items_template_money_note')),
            referenceRow(
                'discount_type',
                __('tenant.excel_optional'),
                __('tenant.price_items_template_discount_type_note'),
                DISCOUNT_TYPES.join(', '),
            ),
            referenceRow(
                'discount_value',
                __('tenant.excel_required_when_discount_type'),
                __('tenant.price_items_template_discount_value_note'),
            ),
            referenceRow('warranty_days', __('tenant.excel_optional'), __('tenant.price_items_template_integer_note')),
            referenceRow('warranty_description_ar', __('tenant.excel_optional'), __('tenant.price_items_template_optional_text_note')),
            referenceRow('warranty_description_en', __('tenant.excel_optional'), __('tenant.price_items_template_optional_text_note')),
            referenceRow('sort_order', __('tenant.excel_optional'), __('tenant.price_items_template_sort_order_note')),
            referenceRow('is_active', __('tenant.excel_optional'), __('tenant.price_items_template_active_note'), '1, 0'),
        ];

        XLSX.utils.book_append_sheet(workbook, rowsSheet, __('tenant.price_items_template_sheet'));
        XLSX.utils.book_append_sheet(workbook, XLSX.utils.json_to_sheet(categoryRows), __('tenant.categories_reference_sheet'));
        XLSX.utils.book_append_sheet(workbook, XLSX.utils.json_to_sheet(columnReference), __('tenant.instructions_sheet'));
        XLSX.writeFile(workbook, 'price-items-template.xlsx');
    };

    const uploadTemplate = async (event) => {
        const file = event.target.files?.[0];
        event.target.value = '';

        if (!file) {
            return;
        }

        const buffer = await file.arrayBuffer();
        const workbook = XLSX.read(buffer, { type: 'array' });
        const worksheet = workbook.Sheets[workbook.SheetNames[0]];
        const rows = XLSX.utils
            .sheet_to_json(worksheet, { defval: '' })
            .filter((row) => TEMPLATE_COLUMNS.some((column) => row[column] !== ''));

        if (rows.length === 0) {
            alert(__('tenant.price_items_import_empty'));
            return;
        }

        router.post(
            route('price-items.import'),
            {
                company_price_list_id: importPriceListId,
                rows,
            },
            {
                preserveScroll: true,
                onStart: () => setIsImporting(true),
                onError: () => setActiveTab('import'),
                onFinish: () => setIsImporting(false),
            },
        );
    };

    const hasActiveFilters = Boolean(
        (filters.search ?? '').trim() || (filters.status ?? 'all') !== 'all' || (filters.price_list_id ?? '') !== '',
    );

    const requestParams = useCallback(
        (nextPerPage = perPage) => ({
            search: search || undefined,
            status: status || 'all',
            price_list_id: priceListId || undefined,
            per_page: nextPerPage || undefined,
        }),
        [perPage, priceListId, search, status],
    );

    const applyFilters = () => {
        setIsFiltering(true);
        router.get(route('price-items.index'), requestParams(), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => setIsFiltering(false),
        });
    };

    const resetFilters = () => {
        setSearch('');
        setStatus('all');
        setPriceListId('');
        setPerPage(10);
        setIsFiltering(true);
        router.get(route('price-items.index'), {}, {
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

        router.delete(route('price-items.destroy', id), { preserveScroll: true });
    }, []);

    const tableColumns = useMemo(
        () => [
            {
                id: 'name',
                key: 'name',
                label: __('tenant.item_name_ar'),
                header: __('tenant.item_name_ar'),
                render: (row) => <strong>{row.name_ar}</strong>,
                searchText: (row) => `${row.name_ar ?? ''} ${row.name_en ?? ''}`.trim(),
            },
            {
                id: 'price_list',
                key: 'price_list',
                label: __('tenant.select_price_list'),
                header: __('tenant.select_price_list'),
                render: (row) => row.price_list?.name ?? '—',
                searchText: (row) => row.price_list?.name ?? '',
            },
            {
                id: 'category',
                key: 'category',
                label: __('tenant.category'),
                header: __('tenant.category'),
                render: (row) => row.service_category?.name_ar ?? '—',
                searchText: (row) => row.service_category?.name_ar ?? '',
            },
            {
                id: 'price_type',
                key: 'price_type',
                label: __('tenant.price_type'),
                header: __('tenant.price_type'),
                render: (row) => displayPriceType(row.price_type),
                searchText: (row) => displayPriceType(row.price_type),
            },
            {
                id: 'price',
                key: 'price',
                label: __('tenant.price'),
                header: __('tenant.price'),
                render: (row) => <span dir="ltr">{row.price ?? '—'}</span>,
            },
            {
                id: 'discount',
                key: 'discount',
                label: __('tenant.effective_discount'),
                header: __('tenant.effective_discount'),
                render: (row) => (
                    <span dir="ltr">{displayDiscount(row.effective_discount_type, row.effective_discount_value)}</span>
                ),
                searchText: (row) => displayDiscount(row.effective_discount_type, row.effective_discount_value),
            },
            {
                id: 'discounted_price',
                key: 'discounted_price',
                label: __('tenant.price_after_discount'),
                header: __('tenant.price_after_discount'),
                render: (row) => <span dir="ltr">{row.discounted_price ?? row.price ?? '—'}</span>,
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
                        <Link href={route('price-items.edit', row.id)} className="btn btn-outline-primary btn-sm">
                            {__('tenant.edit')}
                        </Link>
                        <button type="button" className="btn btn-outline-danger btn-sm" onClick={() => destroy(row.id)}>
                            {__('tenant.delete')}
                        </button>
                    </div>
                ),
            },
        ],
        [destroy, displayDiscount, displayPriceType],
    );

    const resolvedVisibleColumnIds = visibleColumnIds.length > 0 ? visibleColumnIds : tableColumns.map((column) => column.id);
    const visibleTableColumns = tableColumns.filter((column) => resolvedVisibleColumnIds.includes(column.id));

    return (
        <CompanyDashboardLayout breadcrumbKey="dashboard.nav.company_price_items">
            <Head title={__('tenant.price_items')} />

            <div className="card card-outline card-primary">
                <div className="card-header p-0 border-bottom-0">
                    <ul className="nav nav-tabs">
                        <li className="nav-item">
                            <button
                                type="button"
                                className={`nav-link${activeTab === 'items' ? ' active' : ''}`}
                                onClick={() => setActiveTab('items')}
                            >
                                {__('tenant.price_items')}
                            </button>
                        </li>
                        <li className="nav-item">
                            <button
                                type="button"
                                className={`nav-link${activeTab === 'import' ? ' active' : ''}`}
                                onClick={() => setActiveTab('import')}
                            >
                                {__('tenant.price_items_excel_import')}
                            </button>
                        </li>
                    </ul>
                </div>
                <div className="card-body">
                    {activeTab === 'items' && (
                        <>
                            <div
                                className={
                                    hasActiveFilters ? 'card card-outline card-primary' : 'card card-outline card-primary collapsed-card'
                                }
                            >
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
                                        <div className="col-sm-12 col-md-5 col-lg-5">
                                            <input
                                                type="search"
                                                className="form-control"
                                                value={search}
                                                onChange={(e) => setSearch(e.target.value)}
                                                placeholder={__('tenant.search_price_items_placeholder')}
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
                                        <div className="col-sm-12 col-md-4 col-lg-4">
                                            <select
                                                className="custom-select"
                                                value={priceListId}
                                                onChange={(e) => setPriceListId(e.target.value)}
                                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                                            >
                                                <option value="">{__('tenant.filter_by_list')}</option>
                                                {priceLists.map((priceList) => (
                                                    <option key={priceList.id} value={priceList.id}>
                                                        {priceList.name}
                                                    </option>
                                                ))}
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
                                title={__('tenant.price_items')}
                                actions={
                                    <Link href={route('price-items.create')} className="btn btn-success btn-sm">
                                        <i className={isRtl ? 'fas fa-plus ml-1' : 'fas fa-plus mr-1'} />
                                        {__('tenant.add_price_item')}
                                    </Link>
                                }
                                actionsVariant="card-tools"
                                bodyPadding={false}
                            >
                                <div className="p-3 border-bottom">
                                    <TableToolbar
                                        rows={priceItems.data}
                                        columns={tableColumns}
                                        showSearch={false}
                                        exportFilename="tenant-price-items"
                                        persistKey="tenant.price-items.index"
                                        onVisibleColumnIdsChange={(ids) => setVisibleColumnIds(ids)}
                                        showPerPage
                                        perPage={perPage}
                                        perPageOptions={[10, 25, 50, 100]}
                                        onPerPageChange={(value) => {
                                            const next = Number(value) || 10;
                                            setPerPage(next);
                                            setIsFiltering(true);
                                            router.get(route('price-items.index'), requestParams(next), {
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
                                    rows={priceItems.data}
                                    rowKey={(row) => row.id}
                                    emptyTitle={__('tenant.price_items_empty')}
                                />
                                <div className="px-3 pb-3">
                                    <Pagination
                                        links={priceItems.links}
                                        data={{
                                            current_page: priceItems.current_page,
                                            last_page: priceItems.last_page,
                                            prev_page_url: priceItems.prev_page_url,
                                            next_page_url: priceItems.next_page_url,
                                            from: priceItems.from,
                                            to: priceItems.to,
                                            total: priceItems.total,
                                        }}
                                    />
                                </div>
                            </TableCard>
                        </>
                    )}

                    {activeTab === 'import' && (
                        <div className="card card-outline card-info mb-0">
                            <div className="card-header">
                                <h3 className="card-title">{__('tenant.price_items_excel_import')}</h3>
                            </div>
                            <div className="card-body">
                                <p className="text-muted mb-3">{__('tenant.price_items_excel_import_help')}</p>
                                <div className="form-group">
                                    <label>{__('tenant.select_price_list')}</label>
                                    <select
                                        className="custom-select"
                                        value={importPriceListId}
                                        onChange={(event) => setImportPriceListId(event.target.value)}
                                        style={{ textAlign: isRtl ? 'right' : 'left' }}
                                    >
                                        {priceLists.map((priceList) => (
                                            <option key={priceList.id} value={priceList.id}>
                                                {priceList.name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.company_price_list_id && (
                                        <span className="text-danger small d-block">{errors.company_price_list_id}</span>
                                    )}
                                </div>
                                {importCategories.length === 0 && (
                                    <div className="alert alert-warning py-2">{__('tenant.price_list_has_no_categories')}</div>
                                )}
                                <div className="d-flex flex-wrap align-items-center">
                                    <button
                                        type="button"
                                        className="btn btn-outline-primary btn-sm mr-2 mb-2"
                                        onClick={downloadTemplate}
                                        disabled={!importPriceListId || importCategories.length === 0}
                                    >
                                        <i className="fas fa-download mr-1" />
                                        {__('tenant.download_price_items_template')}
                                    </button>
                                    <label
                                        className={`btn btn-outline-success btn-sm mb-2${
                                            !importPriceListId || importCategories.length === 0 || isImporting ? ' disabled' : ''
                                        }`}
                                    >
                                        <i className="fas fa-upload mr-1" />
                                        {isImporting ? __('tenant.importing') : __('tenant.upload_filled_template')}
                                        <input
                                            type="file"
                                            accept=".xlsx,.xls"
                                            className="d-none"
                                            disabled={!importPriceListId || importCategories.length === 0 || isImporting}
                                            onChange={uploadTemplate}
                                        />
                                    </label>
                                </div>
                                {importErrors.length > 0 && (
                                    <ul className="text-danger small mt-3 mb-0">
                                        {importErrors.slice(0, 8).map(([key, message]) => (
                                            <li key={key}>{message}</li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </CompanyDashboardLayout>
    );
}
