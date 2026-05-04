import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import DataTable from '@/Components/ui/DataTable';
import StatusBadge from '@/Components/ui/StatusBadge';
import TableCard from '@/Components/ui/TableCard';
import { Head, Link } from '@inertiajs/react';

type Row = {
    ulid: string;
    type: string;
    subject: string;
    status: string;
    priority: string;
    company: { id: number; name: string } | null;
};

type Paginated<T> = { data: T[] };

export default function PlatformFeedbackIndex({
    tickets,
    filters,
}: {
    tickets: Paginated<Row>;
    filters: { status: string; type: string };
}) {
    return (
        <PlatformAdminLayout header={<h1 className="m-0 text-2xl font-weight-bold text-dark">Feedback & complaints</h1>}>
            <Head title="Feedback" />

            <TableCard
                actions={
                    <Link href={route('platform.dashboard')} className="btn btn-outline-secondary btn-sm">
                        ← Overview
                    </Link>
                }
                bodyPadding={false}
            >
                <DataTable<Row>
                    columns={[
                        { key: 'subject', header: 'Subject', render: (r) => r.subject },
                        { key: 'type', header: 'Type', render: (r) => <StatusBadge variant="secondary">{r.type}</StatusBadge> },
                        { key: 'status', header: 'Status', render: (r) => <StatusBadge variant="info">{r.status}</StatusBadge> },
                        { key: 'company', header: 'Company', render: (r) => r.company?.name ?? '—' },
                        {
                            key: 'actions',
                            header: <span className="d-block text-end">Actions</span>,
                            className: 'text-end',
                            render: (r) => (
                                <Link
                                    href={route('platform.feedback-tickets.show', r.ulid)}
                                    className="btn btn-outline-primary btn-sm"
                                >
                                    Open
                                </Link>
                            ),
                        },
                    ]}
                    rows={tickets.data}
                    rowKey={(r) => r.ulid}
                    emptyTitle="No tickets."
                />
            </TableCard>
        </PlatformAdminLayout>
    );
}
