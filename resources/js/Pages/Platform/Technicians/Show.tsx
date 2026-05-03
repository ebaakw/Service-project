import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { Head, Link } from '@inertiajs/react';

type CompanyRow = { id: number; name: string; slug: string };

type ServiceCategory = { id: number; name_ar: string; name_en: string | null };

type TechnicianDetail = {
    id: number;
    employee_code: string | null;
    bio: string | null;
    job_title: string | null;
    availability_status: string;
    is_active: boolean;
    company: CompanyRow;
    user: { name: string; email: string | null; phone: string | null };
    service_categories: ServiceCategory[];
};

export default function PlatformTechniciansShow({ technician }: { technician: TechnicianDetail }) {
    return (
        <PlatformAdminLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Technician</h2>}>
            <Head title="Technician" />

            <div className="py-10">
                <div className="mx-auto max-w-5xl space-y-8 sm:px-6 lg:px-8">
                    <div>
                        <Link href={route('platform.technicians.index')} className="text-sm text-indigo-600 hover:text-indigo-800">
                            ← Technicians
                        </Link>
                    </div>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <h3 className="text-lg font-semibold text-gray-900">Company</h3>
                        <p className="mt-2 text-sm text-gray-700">{technician.company.name}</p>
                        <p className="text-xs text-gray-500">{technician.company.slug}</p>
                    </div>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <h3 className="text-lg font-semibold text-gray-900">User</h3>
                        <dl className="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt className="text-xs font-medium uppercase text-gray-500">Name</dt>
                                <dd className="text-sm text-gray-900">{technician.user.name}</dd>
                            </div>
                            <div>
                                <dt className="text-xs font-medium uppercase text-gray-500">Email</dt>
                                <dd className="text-sm text-gray-900">{technician.user.email ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs font-medium uppercase text-gray-500">Phone</dt>
                                <dd className="text-sm text-gray-900">{technician.user.phone ?? '—'}</dd>
                            </div>
                        </dl>
                    </div>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <h3 className="text-lg font-semibold text-gray-900">Profile</h3>
                        <dl className="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt className="text-xs font-medium uppercase text-gray-500">Job title</dt>
                                <dd className="text-sm text-gray-900">{technician.job_title ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs font-medium uppercase text-gray-500">Employee code</dt>
                                <dd className="text-sm text-gray-900">{technician.employee_code ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs font-medium uppercase text-gray-500">Availability</dt>
                                <dd className="text-sm text-gray-900">{technician.availability_status}</dd>
                            </div>
                            <div>
                                <dt className="text-xs font-medium uppercase text-gray-500">Active</dt>
                                <dd className="text-sm text-gray-900">{technician.is_active ? 'Yes' : 'No'}</dd>
                            </div>
                            <div className="sm:col-span-2">
                                <dt className="text-xs font-medium uppercase text-gray-500">Bio</dt>
                                <dd className="text-sm text-gray-900">{technician.bio ?? '—'}</dd>
                            </div>
                        </dl>
                    </div>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <h3 className="text-lg font-semibold text-gray-900">Service categories</h3>
                        <ul className="mt-4 list-disc space-y-1 pl-5 text-sm text-gray-800">
                            {technician.service_categories.length === 0 && <li className="list-none text-gray-500">None</li>}
                            {technician.service_categories.map((c) => (
                                <li key={c.id}>
                                    {c.name_ar}
                                    {c.name_en ? ` (${c.name_en})` : ''}
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </div>
        </PlatformAdminLayout>
    );
}
