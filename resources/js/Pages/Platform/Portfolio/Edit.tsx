import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';

type Option = { id: number; name_ar: string; name_en: string | null };
type Tech = { id: number; user: { name: string } };
type Co = { id: number; name: string };
type ImageRow = { id: number; url: string; sort_order: number };

type Project = {
    id: number;
    company_id: number;
    title_ar: string;
    title_en: string | null;
    description_ar: string | null;
    description_en: string | null;
    location: string | null;
    completed_at: string | null;
    is_featured: boolean;
    is_active: boolean;
    service_category_id: number | null;
    technician_id: number | null;
    images: ImageRow[];
};

export default function PlatformPortfolioEdit({
    project,
    companies,
    categories,
    technicians,
}: {
    project: Project;
    companies: Co[];
    categories: Option[];
    technicians: Tech[];
}) {
    const { data, setData, put, processing, errors } = useForm({
        company_id: project.company_id,
        title_ar: project.title_ar,
        title_en: project.title_en ?? '',
        description_ar: project.description_ar ?? '',
        description_en: project.description_en ?? '',
        location: project.location ?? '',
        completed_at: project.completed_at ?? '',
        is_featured: project.is_featured,
        is_active: project.is_active,
        service_category_id: project.service_category_id === null ? ('' as const) : project.service_category_id,
        technician_id: project.technician_id === null ? ('' as const) : project.technician_id,
    });

    const imageForm = useForm<{ image: File | null }>({ image: null });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('platform.portfolio-projects.update', project.id));
    };

    const uploadImage = (e: React.FormEvent) => {
        e.preventDefault();
        imageForm.post(route('platform.portfolio-projects.media.store', project.id), { forceFormData: true });
    };

    const removeImage = (id: number) => {
        if (!confirm('Remove this image?')) {
            return;
        }
        router.delete(route('platform.portfolio-projects.media.destroy', [project.id, id]));
    };

    return (
        <PlatformAdminLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Edit portfolio</h2>}>
            <Head title="Edit portfolio" />

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
                                value={String(data.company_id)}
                                onChange={(e) => setData('company_id', Number(e.target.value))}
                            >
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
                        <div className="grid gap-4 sm:grid-cols-2">
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
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Technician</label>
                                <select
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                    value={data.technician_id === '' ? '' : String(data.technician_id)}
                                    onChange={(e) =>
                                        setData('technician_id', e.target.value === '' ? '' : Number(e.target.value))
                                    }
                                >
                                    <option value="">—</option>
                                    {technicians.map((t) => (
                                        <option key={t.id} value={t.id}>
                                            {t.user.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
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
                            Save
                        </button>
                    </form>

                    <div className="mt-8 rounded-lg bg-white p-6 shadow">
                        <h3 className="text-lg font-medium text-gray-900">Images</h3>
                        <div className="mt-4 grid gap-4 sm:grid-cols-3">
                            {project.images.map((img) => (
                                <div key={img.id} className="rounded border border-gray-200 p-2">
                                    <img src={img.url} alt="" className="h-32 w-full object-cover" />
                                    <button
                                        type="button"
                                        className="mt-2 text-sm text-red-600 hover:text-red-800"
                                        onClick={() => removeImage(img.id)}
                                    >
                                        Remove
                                    </button>
                                </div>
                            ))}
                        </div>
                        <form onSubmit={uploadImage} className="mt-6 flex flex-wrap items-end gap-4 border-t border-gray-100 pt-4">
                            <input
                                type="file"
                                accept="image/*"
                                onChange={(e) => imageForm.setData('image', e.target.files?.[0] ?? null)}
                            />
                            <button
                                type="submit"
                                disabled={imageForm.processing || !imageForm.data.image}
                                className="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-900 disabled:opacity-50"
                            >
                                Upload image
                            </button>
                            {imageForm.errors.image && <p className="text-sm text-red-600">{imageForm.errors.image}</p>}
                        </form>
                    </div>
                </div>
            </div>
        </PlatformAdminLayout>
    );
}
