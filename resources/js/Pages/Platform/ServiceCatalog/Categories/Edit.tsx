import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

type Category = {
    id: number;
    name_ar: string;
    name_en: string | null;
    slug: string;
    icon_path: string | null;
    description_ar: string | null;
    description_en: string | null;
    sort_order: number;
    is_active: boolean;
};

export default function PlatformServiceCategoriesEdit({ category }: { category: Category }) {
    const { data, setData, put, processing, errors } = useForm({
        name_ar: category.name_ar,
        name_en: category.name_en ?? '',
        slug: category.slug,
        icon_path: category.icon_path ?? '',
        description_ar: category.description_ar ?? '',
        description_en: category.description_en ?? '',
        sort_order: category.sort_order,
        is_active: category.is_active,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('platform.service-categories.update', category.id));
    };

    return (
        <PlatformAdminLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Edit service category</h2>}
        >
            <Head title="Edit service category" />

            <div className="py-10">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link href={route('platform.service-categories.index')} className="text-sm text-indigo-600 hover:text-indigo-800">
                            ← Back
                        </Link>
                    </div>

                    <form onSubmit={submit} className="space-y-6 rounded-lg bg-white p-6 shadow">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Name (AR)</label>
                            <input
                                className="mt-1 w-full rounded-md border-gray-300 shadow-sm"
                                value={data.name_ar}
                                onChange={(e) => setData('name_ar', e.target.value)}
                            />
                            {errors.name_ar && <p className="mt-1 text-sm text-red-600">{errors.name_ar}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Name (EN)</label>
                            <input
                                className="mt-1 w-full rounded-md border-gray-300 shadow-sm"
                                value={data.name_en}
                                onChange={(e) => setData('name_en', e.target.value)}
                            />
                            {errors.name_en && <p className="mt-1 text-sm text-red-600">{errors.name_en}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Slug</label>
                            <input
                                className="mt-1 w-full rounded-md border-gray-300 shadow-sm"
                                value={data.slug}
                                onChange={(e) => setData('slug', e.target.value)}
                            />
                            {errors.slug && <p className="mt-1 text-sm text-red-600">{errors.slug}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Icon path</label>
                            <input
                                className="mt-1 w-full rounded-md border-gray-300 shadow-sm"
                                value={data.icon_path}
                                onChange={(e) => setData('icon_path', e.target.value)}
                            />
                            {errors.icon_path && <p className="mt-1 text-sm text-red-600">{errors.icon_path}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Description (AR)</label>
                            <textarea
                                className="mt-1 w-full rounded-md border-gray-300 shadow-sm"
                                rows={3}
                                value={data.description_ar}
                                onChange={(e) => setData('description_ar', e.target.value)}
                            />
                            {errors.description_ar && <p className="mt-1 text-sm text-red-600">{errors.description_ar}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Description (EN)</label>
                            <textarea
                                className="mt-1 w-full rounded-md border-gray-300 shadow-sm"
                                rows={3}
                                value={data.description_en}
                                onChange={(e) => setData('description_en', e.target.value)}
                            />
                            {errors.description_en && <p className="mt-1 text-sm text-red-600">{errors.description_en}</p>}
                        </div>

                        <div className="flex gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Sort order</label>
                                <input
                                    type="number"
                                    min={0}
                                    className="mt-1 w-full rounded-md border-gray-300 shadow-sm"
                                    value={data.sort_order}
                                    onChange={(e) => setData('sort_order', Number(e.target.value))}
                                />
                                {errors.sort_order && <p className="mt-1 text-sm text-red-600">{errors.sort_order}</p>}
                            </div>
                            <div className="flex items-end gap-2 pb-1">
                                <input
                                    id="is_active"
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                />
                                <label htmlFor="is_active" className="text-sm text-gray-700">
                                    Active
                                </label>
                            </div>
                        </div>

                        <div className="flex justify-end gap-3">
                            <Link
                                href={route('platform.service-categories.index')}
                                className="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                            >
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </PlatformAdminLayout>
    );
}
