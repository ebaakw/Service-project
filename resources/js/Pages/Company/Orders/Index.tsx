import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import DataTable from '@/Components/ui/DataTable';
import Pagination from '@/Components/ui/Pagination';
import StatusBadge from '@/Components/ui/StatusBadge';
import TableCard from '@/Components/ui/TableCard';
import { Head, Link } from '@inertiajs/react';

type OrderRow = {
    id: number;
    order_number: string;
    status: string;
    inspection_payment_status: string;
    final_payment_status: string;
    customer: { user: { name: string } };
    technician: { user: { name: string } } | null;
    service_category: { name_ar: string } | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
};

export default function CompanyOrdersIndex({ orders }: { orders: Paginated<OrderRow> }) {
    return (
        <CompanyDashboardLayout header={<h1 className="m-0 text-2xl font-weight-bold text-dark">Orders</h1>}>
            <Head title="Orders" />

            <TableCard
                actions={
                    <Link href={route('company.dashboard')} className="btn btn-outline-secondary btn-sm">
                        ← Dashboard
                    </Link>
                }
                bodyPadding={false}
            >
                <DataTable<OrderRow>
                    columns={[
                        { key: 'order', header: 'Order', render: (r) => r.order_number },
                        { key: 'customer', header: 'Customer', render: (r) => r.customer.user.name },
                        { key: 'tech', header: 'Technician', render: (r) => r.technician?.user.name ?? '—' },
                        {
                            key: 'status',
                            header: 'Status',
                            render: (r) => <StatusBadge variant="info">{r.status}</StatusBadge>,
                        },
                        {
                            key: 'actions',
                            header: <span className="d-block text-end">Actions</span>,
                            className: 'text-end',
                            render: (r) => (
                                <Link href={route('company.orders.show', r.id)} className="btn btn-outline-primary btn-sm">
                                    View
                                </Link>
                            ),
                        },
                    ]}
                    rows={orders.data}
                    rowKey={(r) => r.id}
                    emptyTitle="No orders yet."
                />
                <div className="px-3 pb-3">
                    <Pagination links={orders.links} data={orders} />
                </div>
            </TableCard>
        </CompanyDashboardLayout>
    );
}
