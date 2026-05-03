import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

type Option = { id: number; name_ar: string; name_en: string | null };
type Co = { id: number; name: string };

export default function PlatformPortfolioCreate({
    companies,
    categories,
}: {
    companies: Co[];
    categories: Option[];
}) {
    const { data, setData, post, processing, errors } = useForm({
        company_id: '' as number | '',
        title_ar: '',
        title_en: '',
        description_ar: '',
        description_en: '',
        location: '',
        completed_at: '',
        is_featured: false,
        is_active: true,
        service_category_id: '' as number | '',
        technician_id: '' as number | '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('platform.portfolio-projects.store'));
    };

    return (
        <PlatformAdminLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">New portfolio project</h2>}>
            <Head title="New portfolio project" />

            <div className="py-10">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link href={route('platform.portfolio-projects.index')} className="text-sm text-indigo-700 hover:text-indigo-900">
                            ← Back
                        </Link>
                    </div>

                    <form onSubmit={submit} className="space-y-4 rounded-lg bg-white p-6 shadow">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Company *</label>
                            <select
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                value={data.company_id === '' ? '' : String(data.company_id)}
                                onChange={(e) =>
                                    setData('company_id', e.target.value === '' ? '' : Number(e.target.value))
                                }
                            >
                                <option value="">Select…</option>
                                {companies.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.name}
                                    </option>
                                ))}
                            </select>
                            {errors.company_id && <p className="mt-1 text-sm text-red-600">{errors.company_id}</p>}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Title (AR) *</label>
                            <input
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                value={data.title_ar}
                                onChange={(e) => setData('title_ar', e.target.value)}
                            />
                            {errors.title_ar && <p className="mt-1 text-sm text-red-600">{errors.title_ar}</p>}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Title (EN)</label>
                            <input
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                value={data.title_en}
                                onChange={(e) => setData('title_en', e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Service category</label>
                            <select
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                value={data.service_category_id === '' ? '' : String(data.service_category_id)}
                                onChange={(e) =>
                                    setData('service_category_id', e.target.value === '' ? '' : Number(e.target.value))
                                }
                            >
                                <option value="">—</option>
                                {categories.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.name_ar}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="flex gap-6">
                            <label className="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    checked={data.is_featured}
                                    onChange={(e) => setData('is_featured', e.target.checked)}
                                />
                                Featured
                            </label>
                            <label className="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                />
                                Active
                            </label>
                        </div>
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Create
                        </button>
                    </form>
                </div>
            </div>
        </PlatformAdminLayout>
    );
}
