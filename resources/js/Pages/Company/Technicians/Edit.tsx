import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import { Head, Link, useForm } from '@inertiajs/react';

type ServiceCategoryOption = { id: number; name_ar: string; name_en: string | null };

type TechnicianPayload = {
    id: number;
    employee_code: string | null;
    display_name_ar: string | null;
    display_name_en: string | null;
    bio_ar: string | null;
    bio_en: string | null;
    job_title_ar: string | null;
    job_title_en: string | null;
    is_active: boolean;
    user: { name: string; name_ar?: string | null; name_en?: string | null; email: string | null; phone: string | null };
    service_category_ids: number[];
};

export default function CompanyTechniciansEdit({
    technician,
    serviceCategories,
}: {
    technician: TechnicianPayload;
    serviceCategories: ServiceCategoryOption[];
}) {
    const { data, setData, patch, processing, errors } = useForm({
        name_ar: technician.user.name_ar ?? technician.user.name,
        name_en: technician.user.name_en ?? '',
        email: technician.user.email ?? '',
        phone: technician.user.phone ?? '',
        password: '',
        password_confirmation: '',
        employee_code: technician.employee_code ?? '',
        display_name_ar: technician.display_name_ar ?? '',
        display_name_en: technician.display_name_en ?? '',
        bio_ar: technician.bio_ar ?? '',
        bio_en: technician.bio_en ?? '',
        job_title_ar: technician.job_title_ar ?? '',
        job_title_en: technician.job_title_en ?? '',
        is_active: technician.is_active,
        service_category_ids: technician.service_category_ids,
    });

    const resetForm = useForm({
        password: '',
        password_confirmation: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(route('company.technicians.update', technician.id));
    };

    const submitReset = (e: React.FormEvent) => {
        e.preventDefault();
        resetForm.post(route('company.technicians.reset-password', technician.id));
    };

    return (
        <CompanyDashboardLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Edit technician</h2>}>
            <Head title="Edit technician" />

            <div className="py-10">
                <div className="mx-auto max-w-3xl space-y-8 sm:px-6 lg:px-8">
                    <div>
                        <Link href={route('company.technicians.index')} className="text-sm text-emerald-700 hover:text-emerald-900">
                            ← Back
                        </Link>
                    </div>

                    <form onSubmit={submit} className="space-y-6 rounded-lg bg-white p-6 shadow">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">User name (Arabic)</label>
                                <input
                                    dir="rtl"
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                    value={data.name_ar}
                                    onChange={(e) => setData('name_ar', e.target.value)}
                                />
                                {errors.name_ar && <p className="mt-1 text-sm text-red-600">{errors.name_ar}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">User name (English)</label>
                                <input
                                    dir="ltr"
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                    value={data.name_en}
                                    onChange={(e) => setData('name_en', e.target.value)}
                                />
                                {errors.name_en && <p className="mt-1 text-sm text-red-600">{errors.name_en}</p>}
                            </div>
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Email</label>
                                <input
                                    type="email"
                                    dir="ltr"
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                />
                                {errors.email && <p className="mt-1 text-sm text-red-600">{errors.email}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Phone</label>
                                <input
                                    dir="ltr"
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                    value={data.phone}
                                    onChange={(e) => setData('phone', e.target.value)}
                                />
                                {errors.phone && <p className="mt-1 text-sm text-red-600">{errors.phone}</p>}
                            </div>
                        </div>
                        <p className="text-xs text-gray-500">Provide at least one of email or phone.</p>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">New password (optional)</label>
                                <input
                                    type="password"
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                />
                                {errors.password && <p className="mt-1 text-sm text-red-600">{errors.password}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Confirm new password</label>
                                <input
                                    type="password"
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                />
                            </div>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Display name (Arabic)</label>
                                <input
                                    dir="rtl"
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                    value={data.display_name_ar}
                                    onChange={(e) => setData('display_name_ar', e.target.value)}
                                />
                                {errors.display_name_ar && <p className="mt-1 text-sm text-red-600">{errors.display_name_ar}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Display name (English)</label>
                                <input
                                    dir="ltr"
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                    value={data.display_name_en}
                                    onChange={(e) => setData('display_name_en', e.target.value)}
                                />
                                {errors.display_name_en && <p className="mt-1 text-sm text-red-600">{errors.display_name_en}</p>}
                            </div>
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Employee code</label>
                                <input
                                    dir="ltr"
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                    value={data.employee_code}
                                    onChange={(e) => setData('employee_code', e.target.value)}
                                />
                                {errors.employee_code && <p className="mt-1 text-sm text-red-600">{errors.employee_code}</p>}
                            </div>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Job title (Arabic)</label>
                                <input
                                    dir="rtl"
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                    value={data.job_title_ar}
                                    onChange={(e) => setData('job_title_ar', e.target.value)}
                                />
                                {errors.job_title_ar && <p className="mt-1 text-sm text-red-600">{errors.job_title_ar}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Job title (English)</label>
                                <input
                                    dir="ltr"
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                    value={data.job_title_en}
                                    onChange={(e) => setData('job_title_en', e.target.value)}
                                />
                                {errors.job_title_en && <p className="mt-1 text-sm text-red-600">{errors.job_title_en}</p>}
                            </div>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Bio (Arabic)</label>
                                <textarea
                                    dir="rtl"
                                    rows={3}
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                    value={data.bio_ar}
                                    onChange={(e) => setData('bio_ar', e.target.value)}
                                />
                                {errors.bio_ar && <p className="mt-1 text-sm text-red-600">{errors.bio_ar}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Bio (English)</label>
                                <textarea
                                    dir="ltr"
                                    rows={3}
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                    value={data.bio_en}
                                    onChange={(e) => setData('bio_en', e.target.value)}
                                />
                                {errors.bio_en && <p className="mt-1 text-sm text-red-600">{errors.bio_en}</p>}
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Service categories</label>
                            <select
                                multiple
                                size={Math.min(10, Math.max(4, serviceCategories.length || 4))}
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                value={data.service_category_ids.map(String)}
                                onChange={(e) => {
                                    const selected = Array.from(e.target.selectedOptions).map((o) => Number(o.value));
                                    setData('service_category_ids', selected);
                                }}
                            >
                                {serviceCategories.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.name_ar}
                                        {c.name_en ? ` — ${c.name_en}` : ''}
                                    </option>
                                ))}
                            </select>
                            {errors.service_category_ids && (
                                <p className="mt-1 text-sm text-red-600">{errors.service_category_ids}</p>
                            )}
                        </div>

                        <div className="flex items-center gap-2">
                            <input
                                id="is_active"
                                type="checkbox"
                                checked={data.is_active}
                                onChange={(e) => setData('is_active', e.target.checked)}
                                className="rounded border-gray-300 text-emerald-600"
                            />
                            <label htmlFor="is_active" className="text-sm text-gray-700">
                                Active
                            </label>
                        </div>

                        <div>
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-700 disabled:opacity-50"
                            >
                                Save changes
                            </button>
                        </div>
                    </form>

                    <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 className="text-lg font-medium text-gray-900">Reset password</h3>
                        <p className="mt-1 text-sm text-gray-500">Set a new password for this technician&apos;s login.</p>
                        <form onSubmit={submitReset} className="mt-4 grid gap-4 sm:grid-cols-2">
                            <div className="sm:col-span-2 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">New password</label>
                                    <input
                                        type="password"
                                        className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                        value={resetForm.data.password}
                                        onChange={(e) => resetForm.setData('password', e.target.value)}
                                    />
                                    {resetForm.errors.password && (
                                        <p className="mt-1 text-sm text-red-600">{resetForm.errors.password}</p>
                                    )}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Confirm password</label>
                                    <input
                                        type="password"
                                        className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                        value={resetForm.data.password_confirmation}
                                        onChange={(e) => resetForm.setData('password_confirmation', e.target.value)}
                                    />
                                </div>
                            </div>
                            <div className="sm:col-span-2">
                                <button
                                    type="submit"
                                    disabled={resetForm.processing}
                                    className="rounded-md border border-emerald-600 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 disabled:opacity-50"
                                >
                                    Reset password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </CompanyDashboardLayout>
    );
}
