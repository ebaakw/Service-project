import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import DataTable from '@/Components/ui/DataTable';
import Pagination from '@/Components/ui/Pagination';
import StatusBadge from '@/Components/ui/StatusBadge';
import TableCard from '@/Components/ui/TableCard';
import { Head } from '@inertiajs/react';

type Row = {
    id: number;
    invoice_number: string;
    status: string;
    total_amount: string;
    company: { name: string } | null;
    customer: { user: { name: string } } | null;
    order: { order_number: string } | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
};

export default function PlatformInvoicesIndex({ invoices }: { invoices: Paginated<Row> }) {
    return (
        <PlatformAdminLayout header={<h1 className="m-0 text-2xl font-weight-bold text-dark">Invoices</h1>}>
            <Head title="Invoices" />

            <TableCard bodyPadding={false}>
                <DataTable<Row>
                    columns={[
                        { key: 'invoice', header: 'Invoice', render: (r) => r.invoice_number },
                        { key: 'order', header: 'Order', render: (r) => r.order?.order_number ?? '—' },
                        { key: 'company', header: 'Company', render: (r) => r.company?.name ?? '—' },
                        { key: 'customer', header: 'Customer', render: (r) => r.customer?.user.name ?? '—' },
                        { key: 'total', header: 'Total', render: (r) => r.total_amount },
                        {
                            key: 'status',
                            header: 'Status',
                            render: (r) => <StatusBadge variant="info">{r.status}</StatusBadge>,
                        },
                    ]}
                    rows={invoices.data}
                    rowKey={(r) => r.id}
                    emptyTitle="No invoices found."
                />
                <div className="px-3 pb-3">
                    <Pagination links={invoices.links} data={invoices} />
                </div>
            </TableCard>
        </PlatformAdminLayout>
    );
}
