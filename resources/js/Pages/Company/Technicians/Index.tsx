import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
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
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Technicians</h2>}
        >
            <Head title="Technicians" />

            <div className="py-10">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="mb-6 flex items-center justify-between">
                        <Link href={route('company.dashboard')} className="text-sm text-emerald-700 hover:text-emerald-900">
                            ← Dashboard
                        </Link>
                        <Link
                            href={route('company.technicians.create')}
                            className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-700"
                        >
                            Add technician
                        </Link>
                    </div>

                    <div className="overflow-hidden rounded-lg bg-white shadow">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Name
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Contact
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Categories
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Active
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 bg-white">
                                {technicians.data.length === 0 && (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-8 text-center text-sm text-gray-500">
                                            No technicians yet.
                                        </td>
                                    </tr>
                                )}
                                {technicians.data.map((t) => (
                                    <tr key={t.id}>
                                        <td className="px-4 py-3 text-sm text-gray-900">{t.user.name}</td>
                                        <td className="px-4 py-3 text-sm text-gray-600">
                                            <div>{t.user.email || '—'}</div>
                                            <div>{t.user.phone || ''}</div>
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-600">
                                            {t.service_categories.map((c) => c.name_ar).join(', ') || '—'}
                                        </td>
                                        <td className="px-4 py-3 text-sm">
                                            <span
                                                className={
                                                    t.is_active
                                                        ? 'rounded-full bg-emerald-50 px-2 py-0.5 text-emerald-800'
                                                        : 'rounded-full bg-gray-100 px-2 py-0.5 text-gray-600'
                                                }
                                            >
                                                {t.is_active ? 'Yes' : 'No'}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-right text-sm">
                                            <Link
                                                href={route('company.technicians.edit', t.id)}
                                                className="text-emerald-700 hover:text-emerald-900"
                                            >
                                                Edit
                                            </Link>
                                            <span className="mx-2 text-gray-300">|</span>
                                            <button
                                                type="button"
                                                onClick={() => deleteTechnician(t.id)}
                                                className="text-red-600 hover:text-red-800"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        <div className="flex flex-wrap gap-2 border-t border-gray-100 px-4 py-3">
                            {technicians.links.map((link, idx) =>
                                link.url ? (
                                    <Link
                                        key={idx}
                                        href={link.url}
                                        className={`rounded px-3 py-1 text-sm ${
                                            link.active ? 'bg-emerald-100 text-emerald-900' : 'text-gray-700 hover:bg-gray-100'
                                        }`}
                                        preserveState
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ) : (
                                    <span
                                        key={idx}
                                        className="rounded px-3 py-1 text-sm text-gray-400"
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ),
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </CompanyDashboardLayout>
    );
}
