import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import DataTable from '@/Components/ui/DataTable';
import Pagination from '@/Components/ui/Pagination';
import StatusBadge from '@/Components/ui/StatusBadge';
import TableCard from '@/Components/ui/TableCard';
import { Head, Link } from '@inertiajs/react';

type CompanyRow = { id: number; name: string };

type ServiceCategory = { id: number; name_ar: string; name_en: string | null };

type TechnicianRow = {
    id: number;
    is_active: boolean;
    company: CompanyRow;
    user: { name: string; email: string | null; phone: string | null };
    service_categories: ServiceCategory[];
};

type Paginator<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

export default function PlatformTechniciansIndex({ technicians }: { technicians: Paginator<TechnicianRow> }) {
    return (
        <PlatformAdminLayout
            header={<h1 className="m-0 text-2xl font-weight-bold text-dark">Technicians</h1>}
        >
            <Head title="Technicians" />

            <TableCard bodyPadding={false}>
                <DataTable<TechnicianRow>
                    columns={[
                        { key: 'name', header: 'Name', render: (r) => r.user.name },
                        { key: 'company', header: 'Company', render: (r) => r.company.name },
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
                            key: 'categories',
                            header: 'Categories',
                            render: (r) =>
                                r.service_categories.map((c) => c.name_ar).join(', ') || '—',
                        },
                        {
                            key: 'active',
                            header: 'Active',
                            render: (r) =>
                                r.is_active ? (
                                    <StatusBadge variant="success">Yes</StatusBadge>
                                ) : (
                                    <StatusBadge variant="secondary">No</StatusBadge>
                                ),
                        },
                        {
                            key: 'actions',
                            header: <span className="d-block text-end">Actions</span>,
                            className: 'text-end',
                            render: (r) => (
                                <Link
                                    href={route('platform.technicians.show', r.id)}
                                    className="btn btn-outline-primary btn-sm"
                                >
                                    View
                                </Link>
                            ),
                        },
                    ]}
                    rows={technicians.data}
                    rowKey={(r) => r.id}
                    emptyTitle="No technicians yet."
                />
                <div className="px-3 pb-3">
                    <Pagination links={technicians.links} />
                </div>
            </TableCard>
        </PlatformAdminLayout>
    );
}
