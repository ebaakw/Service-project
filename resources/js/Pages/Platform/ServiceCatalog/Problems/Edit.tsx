import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

type Category = { id: number; name_ar: string };
type Problem = {
    id: number;
    title_ar: string;
    title_en: string | null;
    description_ar: string | null;
    description_en: string | null;
    is_other: boolean;
    sort_order: number;
    is_active: boolean;
};

export default function PlatformServiceProblemsEdit({
    category,
    problem,
}: {
    category: Category;
    problem: Problem;
}) {
    const { data, setData, put, processing, errors } = useForm({
        title_ar: problem.title_ar,
        title_en: problem.title_en ?? '',
        description_ar: problem.description_ar ?? '',
        description_en: problem.description_en ?? '',
        is_other: problem.is_other,
        sort_order: problem.sort_order,
        is_active: problem.is_active,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('platform.service-categories.problems.update', [category.id, problem.id]));
    };

    return (
        <PlatformAdminLayout header={<h2 className="text-xl font-semibold text-gray-800">Edit problem — {category.name_ar}</h2>}>
            <Head title="Edit service problem" />

            <div className="py-10">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link
                            href={route('platform.service-categories.problems.index', category.id)}
                            className="text-sm text-indigo-600 hover:text-indigo-800"
                        >
                            ← Back
                        </Link>
                    </div>

                    <form onSubmit={submit} className="space-y-6 rounded-lg bg-white p-6 shadow">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Title (AR)</label>
                            <input
                                className="mt-1 w-full rounded-md border-gray-300 shadow-sm"
                                value={data.title_ar}
                                onChange={(e) => setData('title_ar', e.target.value)}
                            />
                            {errors.title_ar && <p className="mt-1 text-sm text-red-600">{errors.title_ar}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Title (EN)</label>
                            <input
                                className="mt-1 w-full rounded-md border-gray-300 shadow-sm"
                                value={data.title_en}
                                onChange={(e) => setData('title_en', e.target.value)}
                            />
                            {errors.title_en && <p className="mt-1 text-sm text-red-600">{errors.title_en}</p>}
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

                        <div className="flex flex-wrap gap-6">
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
                            <div className="flex items-end gap-4 pb-1">
                                <label className="flex items-center gap-2 text-sm text-gray-700">
                                    <input
                                        type="checkbox"
                                        checked={data.is_other}
                                        onChange={(e) => setData('is_other', e.target.checked)}
                                    />
                                    “Other” option
                                </label>
                                <label className="flex items-center gap-2 text-sm text-gray-700">
                                    <input
                                        type="checkbox"
                                        checked={data.is_active}
                                        onChange={(e) => setData('is_active', e.target.checked)}
                                    />
                                    Active
                                </label>
                            </div>
                        </div>

                        <div className="flex justify-end gap-3">
                            <Link
                                href={route('platform.service-categories.problems.index', category.id)}
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
