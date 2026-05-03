import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import { Head, Link } from '@inertiajs/react';

type Row = {
    id: number;
    title_ar: string;
    title_en: string | null;
    is_featured: boolean;
    is_active: boolean;
    service_category: { id: number; name_ar: string } | null;
    technician: { id: number; user: { name: string } } | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

export default function CompanyPortfolioIndex({
    projects,
    filters,
}: {
    projects: Paginated<Row>;
    filters: { search: string };
}) {
    return (
        <CompanyDashboardLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Portfolio</h2>}>
            <Head title="Portfolio" />

            <div className="py-10">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="mb-6 flex items-center justify-between">
                        <Link href={route('company.dashboard')} className="text-sm text-emerald-700 hover:text-emerald-900">
                            ← Dashboard
                        </Link>
                        <Link
                            href={route('company.portfolio-projects.create')}
                            className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-700"
                        >
                            New project
                        </Link>
                    </div>

                    <div className="overflow-hidden rounded-lg bg-white shadow">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Title</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Category</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Technician</th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 bg-white">
                                {projects.data.length === 0 && (
                                    <tr>
                                        <td colSpan={4} className="px-4 py-8 text-center text-sm text-gray-500">
                                            No portfolio projects yet.
                                        </td>
                                    </tr>
                                )}
                                {projects.data.map((p) => (
                                    <tr key={p.id}>
                                        <td className="px-4 py-3 text-sm text-gray-900">{p.title_ar}</td>
                                        <td className="px-4 py-3 text-sm text-gray-600">{p.service_category?.name_ar ?? '—'}</td>
                                        <td className="px-4 py-3 text-sm text-gray-600">{p.technician?.user.name ?? '—'}</td>
                                        <td className="px-4 py-3 text-right text-sm">
                                            <Link
                                                href={route('company.portfolio-projects.edit', p.id)}
                                                className="text-emerald-700 hover:text-emerald-900"
                                            >
                                                Edit
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </CompanyDashboardLayout>
    );
}
