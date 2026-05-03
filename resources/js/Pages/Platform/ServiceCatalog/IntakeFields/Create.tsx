import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo } from 'react';

type Category = { id: number; name_ar: string };
type Problem = { id: number; title_ar: string; title_en: string | null };
type OptionRow = { value: string; label_ar: string; label_en: string };

const FIELD_TYPES = [
    'text',
    'textarea',
    'number',
    'select',
    'multi_select',
    'boolean',
    'date',
    'image',
] as const;

export default function PlatformIntakeFieldsCreate({
    category,
    problems,
}: {
    category: Category;
    problems: Problem[];
}) {
    const { data, setData, post, processing, errors, transform } = useForm({
        service_problem_id: '' as string | number,
        label_ar: '',
        label_en: '',
        field_type: 'text' as (typeof FIELD_TYPES)[number] | string,
        options: [] as OptionRow[],
        placeholder_ar: '',
        placeholder_en: '',
        is_required: false,
        sort_order: 0,
        is_active: true,
    });

    const needsOptions = useMemo(
        () => data.field_type === 'select' || data.field_type === 'multi_select',
        [data.field_type]
    );

    const addOption = () => {
        setData('options', [...data.options, { value: '', label_ar: '', label_en: '' }]);
    };

    const updateOption = (index: number, key: keyof OptionRow, value: string) => {
        const next = data.options.map((o, i) => (i === index ? { ...o, [key]: value } : o));
        setData('options', next);
    };

    const removeOption = (index: number) => {
        setData(
            'options',
            data.options.filter((_, i) => i !== index)
        );
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        transform((raw) => {
            const type = String(raw.field_type);
            const wantsOptions = type === 'select' || type === 'multi_select';

            return {
                ...raw,
                service_problem_id: raw.service_problem_id === '' ? null : Number(raw.service_problem_id),
                options: wantsOptions ? raw.options : [],
            };
        });
        post(route('platform.service-categories.intake-fields.store', category.id));
    };

    return (
        <PlatformAdminLayout header={<h2 className="text-xl font-semibold text-gray-800">New intake field — {category.name_ar}</h2>}>
            <Head title="New intake field" />

            <div className="py-10">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link
                            href={route('platform.service-categories.intake-fields.index', category.id)}
                            className="text-sm text-indigo-600 hover:text-indigo-800"
                        >
                            ← Back
                        </Link>
                    </div>

                    <form onSubmit={submit} className="space-y-6 rounded-lg bg-white p-6 shadow">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Scope (optional problem)</label>
                            <select
                                className="mt-1 w-full rounded-md border-gray-300 shadow-sm"
                                value={data.service_problem_id}
                                onChange={(e) =>
                                    setData('service_problem_id', e.target.value === '' ? '' : Number(e.target.value))
                                }
                            >
                                <option value="">All / category only</option>
                                {problems.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.title_ar}
                                    </option>
                                ))}
                            </select>
                            {errors.service_problem_id && <p className="mt-1 text-sm text-red-600">{errors.service_problem_id}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Label (AR)</label>
                            <input
                                className="mt-1 w-full rounded-md border-gray-300 shadow-sm"
                                value={data.label_ar}
                                onChange={(e) => setData('label_ar', e.target.value)}
                            />
                            {errors.label_ar && <p className="mt-1 text-sm text-red-600">{errors.label_ar}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Label (EN)</label>
                            <input
                                className="mt-1 w-full rounded-md border-gray-300 shadow-sm"
                                value={data.label_en}
                                onChange={(e) => setData('label_en', e.target.value)}
                            />
                            {errors.label_en && <p className="mt-1 text-sm text-red-600">{errors.label_en}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Field type</label>
                            <select
                                className="mt-1 w-full rounded-md border-gray-300 shadow-sm"
                                value={data.field_type}
                                onChange={(e) => setData('field_type', e.target.value)}
                            >
                                {FIELD_TYPES.map((t) => (
                                    <option key={t} value={t}>
                                        {t}
                                    </option>
                                ))}
                            </select>
                            {errors.field_type && <p className="mt-1 text-sm text-red-600">{errors.field_type}</p>}
                        </div>

                        {needsOptions && (
                            <div className="rounded-md border border-gray-200 p-4">
                                <div className="mb-3 flex items-center justify-between">
                                    <span className="text-sm font-medium text-gray-700">Options</span>
                                    <button type="button" className="text-sm text-indigo-600 hover:text-indigo-800" onClick={addOption}>
                                        + Add option
                                    </button>
                                </div>
                                {data.options.map((opt, idx) => (
                                    <div key={idx} className="mb-3 grid gap-2 rounded-md bg-gray-50 p-3 md:grid-cols-3">
                                        <input
                                            placeholder="value"
                                            className="rounded-md border-gray-300 shadow-sm"
                                            value={opt.value}
                                            onChange={(e) => updateOption(idx, 'value', e.target.value)}
                                        />
                                        <input
                                            placeholder="label_ar"
                                            className="rounded-md border-gray-300 shadow-sm"
                                            value={opt.label_ar}
                                            onChange={(e) => updateOption(idx, 'label_ar', e.target.value)}
                                        />
                                        <div className="flex gap-2">
                                            <input
                                                placeholder="label_en"
                                                className="w-full rounded-md border-gray-300 shadow-sm"
                                                value={opt.label_en}
                                                onChange={(e) => updateOption(idx, 'label_en', e.target.value)}
                                            />
                                            <button
                                                type="button"
                                                className="text-sm text-red-600"
                                                onClick={() => removeOption(idx)}
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                ))}
                                {errors.options && <p className="mt-1 text-sm text-red-600">{String(errors.options)}</p>}
                            </div>
                        )}

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Placeholder (AR)</label>
                            <input
                                className="mt-1 w-full rounded-md border-gray-300 shadow-sm"
                                value={data.placeholder_ar}
                                onChange={(e) => setData('placeholder_ar', e.target.value)}
                            />
                            {errors.placeholder_ar && <p className="mt-1 text-sm text-red-600">{errors.placeholder_ar}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Placeholder (EN)</label>
                            <input
                                className="mt-1 w-full rounded-md border-gray-300 shadow-sm"
                                value={data.placeholder_en}
                                onChange={(e) => setData('placeholder_en', e.target.value)}
                            />
                            {errors.placeholder_en && <p className="mt-1 text-sm text-red-600">{errors.placeholder_en}</p>}
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
                                        checked={data.is_required}
                                        onChange={(e) => setData('is_required', e.target.checked)}
                                    />
                                    Required
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
                                href={route('platform.service-categories.intake-fields.index', category.id)}
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
