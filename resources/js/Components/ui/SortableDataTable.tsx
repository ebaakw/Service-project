import type { DataTableColumn } from '@/Components/ui/DataTable';
import EmptyState from '@/Components/ui/EmptyState';
import {
    DndContext,
    KeyboardSensor,
    PointerSensor,
    closestCenter,
    type DragEndEvent,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import {
    SortableContext,
    arrayMove,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import classNames from 'classnames';
import type { CSSProperties, ReactNode } from 'react';

function SortableRow<Row>({
    row,
    rowId,
    columns,
    disabled,
    dragHandleLabel,
}: {
    row: Row;
    rowId: string | number;
    columns: Array<DataTableColumn<Row>>;
    disabled?: boolean;
    dragHandleLabel: string;
}) {
    const {
        attributes,
        listeners,
        setNodeRef,
        setActivatorNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({
        id: rowId,
        disabled,
    });

    const style: CSSProperties = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.65 : undefined,
    };

    return (
        <tr ref={setNodeRef} style={style} className={classNames({ 'table-active': isDragging })}>
            <td className="text-center align-middle" style={{ width: 40 }}>
                <button
                    type="button"
                    ref={setActivatorNodeRef}
                    className="btn btn-link btn-sm p-0 text-muted"
                    aria-label={dragHandleLabel}
                    disabled={disabled}
                    style={{ cursor: disabled ? 'not-allowed' : 'grab' }}
                    {...attributes}
                    {...listeners}
                >
                    <i className="fas fa-grip-vertical" />
                </button>
            </td>
            {columns.map((c) => (
                <td key={c.key} className={c.className}>
                    {c.render(row)}
                </td>
            ))}
        </tr>
    );
}

export default function SortableDataTable<Row>({
    columns,
    rows,
    rowKey,
    emptyTitle,
    emptyDescription,
    tableClassName,
    responsive = true,
    disabled = false,
    dragHandleLabel,
    onReorder,
}: {
    columns: Array<DataTableColumn<Row>>;
    rows: Row[];
    rowKey: (row: Row) => string | number;
    emptyTitle: ReactNode;
    emptyDescription?: ReactNode;
    tableClassName?: string;
    responsive?: boolean;
    disabled?: boolean;
    dragHandleLabel: string;
    onReorder: (orderedIds: Array<string | number>) => void;
}) {
    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 6 } }),
        useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
    );

    const ids = rows.map((r) => rowKey(r));

    const handleDragEnd = (event: DragEndEvent) => {
        if (disabled) {
            return;
        }

        const { active, over } = event;
        if (!over || String(active.id) === String(over.id)) {
            return;
        }

        const oldIndex = ids.findIndex((id) => String(id) === String(active.id));
        const newIndex = ids.findIndex((id) => String(id) === String(over.id));
        if (oldIndex < 0 || newIndex < 0) {
            return;
        }

        onReorder(arrayMove(ids, oldIndex, newIndex));
    };

    const table = (
        <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
            <table
                className={classNames(
                    'table table-sm table-hover table-bordered mb-0',
                    tableClassName,
                )}
            >
                <thead className="thead-light">
                    <tr>
                        <th style={{ width: 40 }} aria-hidden="true" />
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
                            <td colSpan={columns.length + 1}>
                                <EmptyState title={emptyTitle} description={emptyDescription} />
                            </td>
                        </tr>
                    ) : (
                        <SortableContext items={ids} strategy={verticalListSortingStrategy}>
                            {rows.map((r) => (
                                <SortableRow
                                    key={rowKey(r)}
                                    row={r}
                                    rowId={rowKey(r)}
                                    columns={columns}
                                    disabled={disabled}
                                    dragHandleLabel={dragHandleLabel}
                                />
                            ))}
                        </SortableContext>
                    )}
                </tbody>
            </table>
        </DndContext>
    );

    return responsive ? <div className="table-responsive">{table}</div> : table;
}
