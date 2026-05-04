import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import DataTable from '@/Components/ui/DataTable';
import Pagination from '@/Components/ui/Pagination';
import StatusBadge from '@/Components/ui/StatusBadge';
import TableCard from '@/Components/ui/TableCard';
import { Head, Link, router } from '@inertiajs/react';

type ServiceCategory = { id: number; name_ar: string; name_en: string | null };

type TechnicianRow = {
    id: number;
    is_active: boolean;
    user: { name: string; email: string | null; phone: string | null };
    service_categories: ServiceCategory[];
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
};

export default function CompanyTechniciansIndex({ technicians }: { technicians: Paginated<TechnicianRow> }) {
    const deleteTechnician = (id: number) => {
        if (!confirm('Delete this technician?')) {
            return;
        }
        router.delete(route('company.technicians.destroy', id));
    };

    return (
        <CompanyDashboardLayout
            header={<h1 className="m-0 text-2xl font-weight-bold text-dark">Technicians</h1>}
        >
            <Head title="Technicians" />

            <TableCard
                actions={
                    <>
                        <Link href={route('company.dashboard')} className="btn btn-outline-secondary btn-sm">
                            ← Dashboard
                        </Link>
                        <Link href={route('company.technicians.create')} className="btn btn-primary btn-sm">
                            Add technician
                        </Link>
                    </>
                }
                bodyPadding={false}
            >
                <DataTable<TechnicianRow>
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
                            key: 'categories',
                            header: 'Categories',
                            render: (r) => r.service_categories.map((c) => c.name_ar).join(', ') || '—',
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
                                <div className="btn-group btn-group-sm">
                                    <Link
                                        href={route('company.technicians.edit', r.id)}
                                        className="btn btn-outline-primary"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        type="button"
                                        onClick={() => deleteTechnician(r.id)}
                                        className="btn btn-outline-danger"
                                    >
                                        Delete
                                    </button>
                                </div>
                            ),
                        },
                    ]}
                    rows={technicians.data}
                    rowKey={(r) => r.id}
                    emptyTitle="No technicians yet."
                />
                <div className="px-3 pb-3">
                    <Pagination links={technicians.links} data={technicians} />
                </div>
            </TableCard>
        </CompanyDashboardLayout>
    );
}
