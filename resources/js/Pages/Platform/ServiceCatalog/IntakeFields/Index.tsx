import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { Head, Link, router } from '@inertiajs/react';

type Category = { id: number; name_ar: string; slug: string };
type ProblemOption = { id: number; title_ar: string };
type FieldRow = {
    id: number;
    label_ar: string;
    label_en: string | null;
    field_type: string;
    is_active: boolean;
    sort_order: number;
    service_problem_id: number | null;
    problem?: { id: number; title_ar: string } | null;
};
type Paginator<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

export default function PlatformIntakeFieldsIndex({
    category,
    problems,
    fields,
    filters,
}: {
    category: Category;
    problems: ProblemOption[];
    fields: Paginator<FieldRow>;
    filters: { search: string; status: string; problem_id: string | null | undefined };
}) {
    const apply = (patch: Record<string, string | null | undefined>) => {
        router.get(
            route('platform.service-categories.intake-fields.index', category.id),
            { ...filters, ...patch },
            { preserveState: true }
        );
    };

    return (
        <PlatformAdminLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-800">Intake fields</h2>
                        <p className="text-sm text-gray-600">
                            {category.name_ar} ({category.slug})
                        </p>
                    </div>
                    <Link
                        href={route('platform.service-categories.intake-fields.create', category.id)}
                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        Add field
                    </Link>
                </div>
            }
        >
            <Head title="Intake fields" />

            <div className="py-10">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <Link
                            href={route('platform.service-categories.index')}
                            className="text-sm text-indigo-600 hover:text-indigo-800"
                        >
                            ← Categories
                        </Link>
                        <div className="flex flex-wrap gap-3 rounded-lg bg-white p-4 shadow">
                            <input
                                type="search"
                                placeholder="Search"
                                defaultValue={filters.search}
                                className="rounded-md border border-gray-300 px-3 py-2 text-sm"
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        apply({ search: (e.target as HTMLInputElement).value });
                                    }
                                }}
                            />
                            <select
                                className="rounded-md border border-gray-300 px-3 py-2 text-sm"
                                value={filters.status}
                                onChange={(e) => apply({ status: e.target.value })}
                            >
                                <option value="all">All statuses</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <select
                                className="rounded-md border border-gray-300 px-3 py-2 text-sm"
                                value={filters.problem_id ?? ''}
                                onChange={(e) => apply({ problem_id: e.target.value || null })}
                            >
                                <option value="">All problems</option>
                                {problems.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.title_ar}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div className="overflow-hidden bg-white shadow sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-start text-xs font-medium uppercase text-gray-500">Label (AR)</th>
                                    <th className="px-4 py-3 text-start text-xs font-medium uppercase text-gray-500">Type</th>
                                    <th className="px-4 py-3 text-start text-xs font-medium uppercase text-gray-500">Problem</th>
                                    <th className="px-4 py-3 text-start text-xs font-medium uppercase text-gray-500">Sort</th>
                                    <th className="px-4 py-3 text-start text-xs font-medium uppercase text-gray-500">Active</th>
                                    <th className="px-4 py-3 text-end text-xs font-medium uppercase text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {fields.data.map((row) => (
                                    <tr key={row.id}>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-900">{row.label_ar}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                            {typeof row.field_type === 'object' && row.field_type && 'value' in row.field_type
                                                ? String((row.field_type as { value: string }).value)
                                                : String(row.field_type)}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                            {row.problem?.title_ar ?? '—'}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{row.sort_order}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{row.is_active ? 'Yes' : 'No'}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm">
                                            <div className="flex justify-end gap-3">
                                                <Link
                                                    href={route('platform.service-categories.intake-fields.edit', [category.id, row.id])}
                                                    className="text-gray-700 hover:text-gray-900"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    type="button"
                                                    className="text-red-600 hover:text-red-800"
                                                    onClick={() => {
                                                        if (confirm('Delete this field?')) {
                                                            router.delete(
                                                                route('platform.service-categories.intake-fields.destroy', [
                                                                    category.id,
                                                                    row.id,
                                                                ])
                                                            );
                                                        }
                                                    }}
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        <div className="flex flex-wrap gap-2 border-t border-gray-200 px-4 py-3">
                            {fields.links.map((link, idx) => (
                                <Link
                                    key={idx}
                                    href={link.url ?? '#'}
                                    className={`rounded px-3 py-1 text-sm ${
                                        link.active ? 'bg-indigo-100 text-indigo-800' : 'text-gray-700 hover:bg-gray-100'
                                    }`}
                                    preserveState
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </PlatformAdminLayout>
    );
}
