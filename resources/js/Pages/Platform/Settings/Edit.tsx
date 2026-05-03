import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import type { PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

type Props = PageProps & {
    values: Record<string, string | number | null>;
    keys: string[];
    meta: Record<string, { description: string | null; group: string | null }>;
};

export default function PlatformSettingsEdit({ values, keys, meta }: Props) {
    const flash = (usePage().props as PageProps & { flash?: { message?: string | null } }).flash;

    const initial: Record<string, string> = {};
    keys.forEach((k) => {
        const v = values[k];
        initial[k] = v === null || v === undefined ? '' : String(v);
    });

    const { data, setData, put, processing, errors } = useForm<{ settings: Record<string, string> }>({
        settings: initial,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('platform.settings.update'));
    };

    return (
        <PlatformAdminLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Global settings</h2>}>
            <Head title="Platform settings" />

            <div className="py-10">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link href={route('platform.dashboard')} className="text-sm text-indigo-700 hover:text-indigo-900">
                            ← Overview
                        </Link>
                    </div>

                    {flash?.message && (
                        <div className="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{flash.message}</div>
                    )}

                    <form onSubmit={submit} className="space-y-6 rounded-lg bg-white p-6 shadow">
                        {keys.map((key) => (
                            <div key={key}>
                                <label className="block text-sm font-medium text-gray-700">
                                    {key}
                                    {meta[key]?.description && (
                                        <span className="ml-2 font-normal text-gray-500">({meta[key]?.description})</span>
                                    )}
                                </label>
                                <input
                                    className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                    value={data.settings[key] ?? ''}
                                    onChange={(e) =>
                                        setData('settings', {
                                            ...data.settings,
                                            [key]: e.target.value,
                                        })
                                    }
                                />
                                {errors[`settings.${key}` as keyof typeof errors] && (
                                    <p className="mt-1 text-sm text-red-600">{errors[`settings.${key}` as keyof typeof errors]}</p>
                                )}
                            </div>
                        ))}

                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Save
                        </button>
                    </form>
                </div>
            </div>
        </PlatformAdminLayout>
    );
}
