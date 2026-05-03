import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import { Head, Link, useForm } from '@inertiajs/react';

type ServiceCategoryOption = { id: number; name_ar: string; name_en: string | null };

export default function CompanyTechniciansCreate({
    serviceCategories,
}: {
    serviceCategories: ServiceCategoryOption[];
}) {
    const { data, setData, post, processing, errors } = useForm({
        name_ar: '',
        name_en: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
        employee_code: '',
        display_name_ar: '',
        display_name_en: '',
        bio_ar: '',
        bio_en: '',
        job_title_ar: '',
        job_title_en: '',
        is_active: true,
        service_category_ids: [] as number[],
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('company.technicians.store'));
    };

    return (
        <CompanyDashboardLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">New technician</h2>}>
            <Head title="New technician" />

            <div className="py-10">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="mb-6">
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
                                <label className="block text-sm font-medium text-gray-700">Password</label>
                                <input
                                    type="password"
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                />
                                {errors.password && <p className="mt-1 text-sm text-red-600">{errors.password}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Confirm password</label>
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
                            {errors['service_category_ids.0'] && (
                                <p className="mt-1 text-sm text-red-600">{errors['service_category_ids.0']}</p>
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
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </CompanyDashboardLayout>
    );
}
