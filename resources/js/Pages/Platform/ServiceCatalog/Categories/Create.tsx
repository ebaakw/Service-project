import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function PlatformServiceCategoriesCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name_ar: '',
        name_en: '',
        slug: '',
        icon_path: '',
        description_ar: '',
        description_en: '',
        sort_order: 0,
        is_active: true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('platform.service-categories.store'));
    };

    return (
        <PlatformAdminLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">New service category</h2>}
        >
            <Head title="New service category" />

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
