import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import classNames from 'classnames';
import debounce from 'lodash.debounce';
import { saveAs } from 'file-saver';
import { CSVLink } from 'react-csv';
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import * as XLSX from 'xlsx';

export type TableToolbarColumn<Row> = {
    id: string;
    label: React.ReactNode;
    render: (row: Row) => React.ReactNode;
    /** If provided, used for global search */
    searchText?: (row: Row) => string;
};

function toPlainText(node: React.ReactNode): string {
    if (node == null) return '';
    if (typeof node === 'string' || typeof node === 'number') return String(node);
    if (Array.isArray(node)) return node.map(toPlainText).join(' ');
    if (typeof node === 'object' && (node as any)?.props?.children !== undefined) {
        return toPlainText((node as any).props.children);
    }
    return String(node);
}

type Props<Row> = {
    rows: Row[];
    columns: Array<TableToolbarColumn<Row>>;

    /** Optional search input (same layout as reference). */
    showSearch?: boolean;
    initialSearch?: string;
    onSearch?: (value: string) => void;

    /** CSV export: either provide server URL OR client rows/columns. */
    exportCsvUrl?: string;
    exportFilename?: string;

    /** Per-page selector (server-side pagination). */
    showPerPage?: boolean;
    perPage?: number;
    perPageOptions?: number[];
    onPerPageChange?: (value: number) => void;

    /** Column visibility */
    persistKey?: string;
    initialVisibleColumnIds?: string[];
    onVisibleColumnIdsChange?: (ids: string[]) => void;
};

export default function TableToolbar<Row>({
    rows,
    columns,
    showSearch = true,
    initialSearch = '',
    onSearch,
    exportCsvUrl,
    exportFilename = 'export',
    showPerPage = false,
    perPage = 10,
    perPageOptions = [10, 25, 50, 100],
    onPerPageChange,
    persistKey,
    initialVisibleColumnIds,
    onVisibleColumnIdsChange,
}: Props<Row>) {
    const storageKey = useMemo(() => {
        const key = String(persistKey ?? '').trim();
        return key ? `services.tableToolbar.v1:${key}` : null;
    }, [persistKey]);

    const allIds = useMemo(() => columns.map((c) => String(c.id)), [columns]);

    const [visibleColumnIds, setVisibleColumnIds] = useState<string[]>(() => {
        let ids = Array.isArray(initialVisibleColumnIds) && initialVisibleColumnIds.length ? initialVisibleColumnIds.map(String) : allIds;

        if (storageKey) {
            try {
                const raw = localStorage.getItem(storageKey);
                if (raw) {
                    const parsed = JSON.parse(raw);
                    const stored = Array.isArray(parsed?.visibleColumnIds) ? parsed.visibleColumnIds : parsed;
                    if (Array.isArray(stored) && stored.length > 0) {
                        const allowed = new Set(allIds);
                        const filtered = stored.map(String).filter((id: string) => allowed.has(String(id)));
                        if (filtered.length > 0) {
                            // Keep saved visibility, but surface any columns added after the last save.
                            const known = new Set(filtered);
                            const added = allIds.filter((id) => !known.has(id));
                            ids = [...filtered, ...added];
                        }
                    }
                }
            } catch {
                // ignore
            }
        }

        return ids;
    });

    useEffect(() => {
        if (!storageKey) return;
        try {
            localStorage.setItem(storageKey, JSON.stringify({ visibleColumnIds }));
        } catch {
            // ignore
        }
    }, [storageKey, visibleColumnIds]);

    useEffect(() => {
        onVisibleColumnIdsChange?.(visibleColumnIds);
    }, [visibleColumnIds, onVisibleColumnIdsChange]);

    const visibleColumns = useMemo(
        () => columns.filter((c) => visibleColumnIds.includes(String(c.id))),
        [columns, visibleColumnIds],
    );

    const [showColumnsMenu, setShowColumnsMenu] = useState(false);
    const columnsMenuRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        if (!showColumnsMenu) return;
        const onKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'Escape') setShowColumnsMenu(false);
        };
        const onMouseDown = (e: MouseEvent) => {
            const el = columnsMenuRef.current;
            if (!el) return;
            if (el.contains(e.target as Node)) return;
            setShowColumnsMenu(false);
        };
        document.addEventListener('keydown', onKeyDown);
        document.addEventListener('mousedown', onMouseDown);
        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.removeEventListener('mousedown', onMouseDown);
        };
    }, [showColumnsMenu]);

    const toggleColumn = (colId: string) => {
        setVisibleColumnIds((prev) => (prev.includes(colId) ? prev.filter((id) => id !== colId) : [...prev, colId]));
    };

    const [searchInput, setSearchInput] = useState(initialSearch);
    useEffect(() => setSearchInput(initialSearch), [initialSearch]);

    const debouncedOnSearch = useMemo(() => {
        if (!showSearch || typeof onSearch !== 'function') {
            return null;
        }
        return debounce((v: string) => onSearch(v), 300);
    }, [onSearch, showSearch]);
    useEffect(() => () => debouncedOnSearch?.cancel(), [debouncedOnSearch]);

    const onSearchChange = useCallback(
        (e: React.ChangeEvent<HTMLInputElement>) => {
            const v = e.target.value;
            setSearchInput(v);
            debouncedOnSearch?.(v);
        },
        [debouncedOnSearch],
    );

    const exportExcel = useCallback(() => {
        try {
            const headers = visibleColumns.map((c) => toPlainText(c.label));
            const aoa = [
                headers,
                ...rows.map((row) => visibleColumns.map((c) => toPlainText(c.render(row)))),
            ];
            const ws = XLSX.utils.aoa_to_sheet(aoa);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Report');
            const out = XLSX.write(wb, { bookType: 'xlsx', type: 'array' });
            const blob = new Blob([out], {
                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            });
            saveAs(blob, `${exportFilename}.xlsx`);
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error('Excel export failed', e);
            window.alert(
                __('tenant.unable_to_export_excel') !== 'tenant.unable_to_export_excel'
                    ? __('tenant.unable_to_export_excel')
                    : 'Unable to export Excel',
            );
        }
    }, [exportFilename, rows, visibleColumns]);

    const printTable = useCallback(() => {
        const printWindow = window.open('', '_blank');
        if (!printWindow) return;

        const headers = visibleColumns.map((c) => toPlainText(c.label)).join('</th><th>');
        const bodyRows = rows
            .map((row) => {
                const cells = visibleColumns
                    .map((c) => `<td>${toPlainText(c.render(row))}</td>`)
                    .join('');
                return `<tr>${cells}</tr>`;
            })
            .join('');

        printWindow.document.write(`
<!DOCTYPE html>
<html dir="rtl">
<head>
  <meta charset="utf-8" />
  <title>${exportFilename}</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    table { border-collapse: collapse !important; }
    th, td { border: 1px solid #000 !important; }
    thead th { font-weight: 700 !important; }
  </style>
</head>
<body class="p-4">
  <table class="table table-bordered table-sm">
    <thead><tr><th>${headers}</th></tr></thead>
    <tbody>${bodyRows}</tbody>
  </table>
</body>
</html>
        `);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => {
            try {
                printWindow.print();
            } finally {
                setTimeout(() => printWindow.close(), 300);
            }
        }, 200);
    }, [exportFilename, rows, visibleColumns]);

    const exportPdf = useCallback(() => {
        // Same behavior as reference: rely on Print dialog -> Save as PDF
        printTable();
    }, [printTable]);

    const csvData = useMemo(() => {
        return rows.map((row) => {
            const obj: Record<string, string> = {};
            visibleColumns.forEach((col) => {
                obj[toPlainText(col.label)] = toPlainText(col.render(row));
            });
            return obj;
        });
    }, [rows, visibleColumns]);

    const placeholder =
        __('tenant.wallets_page_search_placeholder') !== 'tenant.wallets_page_search_placeholder'
            ? __('tenant.wallets_page_search_placeholder')
            : `${__('tenant.search') !== 'tenant.search' ? __('tenant.search') : 'Search'} ...`;

    return (
        <div className="d-flex flex-wrap align-items-center gap-2 mb-3">
            {showSearch ? (
                <input
                    type="text"
                    className="form-control form-control-sm"
                    style={{ maxWidth: '220px' }}
                    placeholder={placeholder}
                    value={searchInput}
                    onChange={onSearchChange}
                />
            ) : null}

            <div className="d-flex flex-wrap align-items-center gap-1">
                {exportCsvUrl ? (
                    <a href={exportCsvUrl} className="btn btn-sm btn-outline-secondary">
                        <i className="fas fa-file-csv me-1" />
                        {__('tenant.export_csv') !== 'tenant.export_csv' ? __('tenant.export_csv') : 'Export CSV'}
                    </a>
                ) : (
                    <CSVLink
                        data={csvData}
                        filename={`${exportFilename}.csv`}
                        className="btn btn-sm btn-outline-secondary"
                        uFEFF
                    >
                        <i className="fas fa-file-csv me-1" />
                        {__('tenant.export_csv') !== 'tenant.export_csv' ? __('tenant.export_csv') : 'Export CSV'}
                    </CSVLink>
                )}

                <button type="button" className="btn btn-sm btn-outline-success" onClick={exportExcel}>
                    <i className="fas fa-file-excel me-1" />
                    {__('tenant.export_excel') !== 'tenant.export_excel' ? __('tenant.export_excel') : 'Export Excel'}
                </button>

                <button type="button" className="btn btn-sm btn-outline-danger" onClick={exportPdf}>
                    <i className="fas fa-file-pdf me-1" />
                    {__('tenant.export_pdf') !== 'tenant.export_pdf' ? __('tenant.export_pdf') : 'Export PDF'}
                </button>

                <button type="button" className="btn btn-sm btn-outline-secondary" onClick={printTable}>
                    <i className="fas fa-print me-1" />
                    {__('tenant.print') !== 'tenant.print' ? __('tenant.print') : 'Print'}
                </button>

                <div className="position-relative d-inline-block" ref={columnsMenuRef}>
                    <button
                        type="button"
                        className="btn btn-sm btn-outline-secondary"
                        onClick={() => setShowColumnsMenu((v) => !v)}
                    >
                        {__('tenant.column_visibility') !== 'tenant.column_visibility'
                            ? __('tenant.column_visibility')
                            : 'Column visibility'}
                    </button>
                    {showColumnsMenu && (
                        <div
                            className={classNames('border bg-white rounded shadow-sm p-2')}
                            style={{
                                position: 'absolute',
                                top: '100%',
                                right: 0,
                                zIndex: 2000,
                                minWidth: 220,
                                maxHeight: 320,
                                overflowY: 'auto',
                            }}
                        >
                            {columns.map((c) => (
                                <label
                                    key={c.id}
                                    className="d-flex align-items-center gap-2 py-1 px-1 mb-0 cursor-pointer"
                                    style={{ userSelect: 'none' }}
                                >
                                    <input
                                        type="checkbox"
                                        checked={visibleColumnIds.includes(String(c.id))}
                                        onChange={() => toggleColumn(String(c.id))}
                                    />
                                    <span className="small">{c.label}</span>
                                </label>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {showPerPage ? (
                <div className="d-flex align-items-center gap-1 ml-auto">
                    <span className="small text-muted">{__('tenant.show') !== 'tenant.show' ? __('tenant.show') : 'Show'}</span>
                    <select
                        className="form-control form-control-sm w-auto"
                        value={perPage}
                        onChange={(e) => onPerPageChange?.(Number(e.target.value))}
                    >
                        {perPageOptions.map((n) => (
                            <option key={n} value={n}>
                                {n}
                            </option>
                        ))}
                    </select>
                    <span className="small text-muted">{__('tenant.entries') !== 'tenant.entries' ? __('tenant.entries') : 'entries'}</span>
                </div>
            ) : null}
        </div>
    );
}

