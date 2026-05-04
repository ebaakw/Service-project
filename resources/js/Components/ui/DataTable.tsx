import type { ReactNode } from 'react';
import classNames from 'classnames';
import EmptyState from '@/Components/ui/EmptyState';

export type DataTableColumn<Row> = {
    key: string;
    header: ReactNode;
    className?: string;
    headerClassName?: string;
    render: (row: Row) => ReactNode;
};

export default function DataTable<Row>({
    columns,
    rows,
    rowKey,
    emptyTitle,
    emptyDescription,
    tableClassName,
    responsive = true,
}: {
    columns: Array<DataTableColumn<Row>>;
    rows: Row[];
    rowKey: (row: Row) => string | number;
    emptyTitle: ReactNode;
    emptyDescription?: ReactNode;
    tableClassName?: string;
    responsive?: boolean;
}) {
    const table = (
        <table
            className={classNames(
                'table table-sm table-hover table-bordered mb-0',
                tableClassName,
            )}
        >
            <thead className="thead-light">
                <tr>
                    {columns.map((c) => (
                        <th key={c.key} className={c.headerClassName}>
                            {c.header}
                        </th>
                    ))}
                </tr>
            </thead>
            <tbody>
                {rows.length === 0 ? (
                    <tr>
                        <td colSpan={columns.length}>
                            <EmptyState
                                title={emptyTitle}
                                description={emptyDescription}
                            />
                        </td>
                    </tr>
                ) : (
                    rows.map((r) => (
                        <tr key={rowKey(r)}>
                            {columns.map((c) => (
                                <td key={c.key} className={c.className}>
                                    {c.render(r)}
                                </td>
                            ))}
                        </tr>
                    ))
                )}
            </tbody>
        </table>
    );

    return responsive ? <div className="table-responsive">{table}</div> : table;
}

