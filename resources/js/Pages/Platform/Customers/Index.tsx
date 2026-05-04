import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import DataTable from '@/Components/ui/DataTable';
import Pagination from '@/Components/ui/Pagination';
import TableCard from '@/Components/ui/TableCard';
import { Head, Link } from '@inertiajs/react';

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
};

export default function PlatformCustomersIndex({ customers }: { customers: Paginator<CustomerRow> }) {
    return (
        <PlatformAdminLayout
            header={<h1 className="m-0 text-2xl font-weight-bold text-dark">Customers</h1>}
        >
            <Head title="Customers" />

            <TableCard bodyPadding={false}>
                <DataTable<CustomerRow>
                    columns={[
                        { key: 'name', header: 'Name', render: (r) => r.user.name },
                        {
                            key: 'contact',
                            header: 'Contact',
                            render: (r) => (
                                <div>
                                    <div>{r.user.email || '—'}</div>
                                    <div>{r.user.phone || '—'}</div>
                                </div>
                            ),
                        },
                        {
                            key: 'address',
                            header: 'Default address',
                            render: (r) => r.default_address?.full_address ?? '—',
                        },
                        {
                            key: 'actions',
                            header: <span className="d-block text-end">Actions</span>,
                            className: 'text-end',
                            render: (r) => (
                                <Link
                                    href={route('platform.customers.show', r.id)}
                                    className="btn btn-outline-primary btn-sm"
                                >
                                    View
                                </Link>
                            ),
                        },
                    ]}
                    rows={customers.data}
                    rowKey={(r) => r.id}
                    emptyTitle="No customers yet."
                />
                <div className="px-3 pb-3">
                    <Pagination links={customers.links} />
                </div>
            </TableCard>
        </PlatformAdminLayout>
    );
}
