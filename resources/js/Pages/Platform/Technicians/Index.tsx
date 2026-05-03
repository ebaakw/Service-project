import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
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
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Technicians</h2>}
        >
            <Head title="Technicians" />

            <div className="py-10">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg bg-white shadow">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-start text-xs font-medium uppercase text-gray-500">Name</th>
                                    <th className="px-4 py-3 text-start text-xs font-medium uppercase text-gray-500">Company</th>
                                    <th className="px-4 py-3 text-start text-xs font-medium uppercase text-gray-500">Contact</th>
                                    <th className="px-4 py-3 text-start text-xs font-medium uppercase text-gray-500">Categories</th>
                                    <th className="px-4 py-3 text-start text-xs font-medium uppercase text-gray-500">Active</th>
                                    <th className="px-4 py-3 text-end text-xs font-medium uppercase text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {technicians.data.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-8 text-center text-sm text-gray-500">
                                            No technicians yet.
                                        </td>
                                    </tr>
                                )}
                                {technicians.data.map((t) => (
                                    <tr key={t.id}>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-900">{t.user.name}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{t.company.name}</td>
                                        <td className="px-4 py-3 text-sm text-gray-700">
                                            <div>{t.user.email || '—'}</div>
                                            <div>{t.user.phone || ''}</div>
                                        </td>
                                        <td className="max-w-md px-4 py-3 text-sm text-gray-700">
                                            {t.service_categories.map((c) => c.name_ar).join(', ') || '—'}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{t.is_active ? 'Yes' : 'No'}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm">
                                            <div className="flex justify-end">
                                                <Link
                                                    href={route('platform.technicians.show', t.id)}
                                                    className="text-indigo-600 hover:text-indigo-800"
                                                >
                                                    View
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        <div className="flex flex-wrap gap-2 border-t border-gray-200 px-4 py-3">
                            {technicians.links.map((link, idx) =>
                                link.url ? (
                                    <Link
                                        key={idx}
                                        href={link.url}
                                        className={`rounded px-3 py-1 text-sm ${
                                            link.active ? 'bg-indigo-100 text-indigo-800' : 'text-gray-700 hover:bg-gray-100'
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
        </PlatformAdminLayout>
    );
}
