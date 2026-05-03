import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import type { PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

type Props = PageProps & {
    values: Record<string, string | number | null>;
    globalValues: Record<string, string | number | null>;
    keys: string[];
    company: {
        id: number;
        name: string;
        logo_path: string | null;
        logo_url: string | null;
    };
};

export default function CompanySettingsEdit({ values, globalValues, keys, company }: Props) {
    const flash = (usePage().props as PageProps & { flash?: { message?: string | null } }).flash;

    const initial: Record<string, string> = {};
    keys.forEach((k) => {
        const v = values[k];
        initial[k] = v === null || v === undefined ? '' : String(v);
    });

    const { data, setData, put, processing, errors } = useForm<{ settings: Record<string, string> }>({
        settings: initial,
    });

    const logoForm = useForm<{ logo: File | null }>({ logo: null });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('company.settings.update'));
    };

    const submitLogo = (e: React.FormEvent) => {
        e.preventDefault();
        logoForm.post(route('company.settings.logo'), { forceFormData: true });
    };

    return (
        <CompanyDashboardLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Company settings</h2>}>
            <Head title="Company settings" />

            <div className="py-10">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="mb-6 flex justify-between">
                        <Link href={route('company.dashboard')} className="text-sm text-emerald-700 hover:text-emerald-900">
                            ← Dashboard
                        </Link>
                    </div>

                    {flash?.message && (
                        <div className="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{flash.message}</div>
                    )}

                    <div className="mb-8 rounded-lg bg-white p-6 shadow">
                        <h3 className="text-lg font-medium text-gray-900">Logo</h3>
                        {company.logo_url && (
                            <img src={company.logo_url} alt="" className="mt-2 h-16 w-auto rounded border border-gray-200 object-contain" />
                        )}
                        <form onSubmit={submitLogo} className="mt-4 flex flex-wrap items-end gap-4">
                            <input
                                type="file"
                                accept="image/*"
                                className="text-sm"
                                onChange={(e) => logoForm.setData('logo', e.target.files?.[0] ?? null)}
                            />
                            <button
                                type="submit"
                                disabled={logoForm.processing || !logoForm.data.logo}
                                className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                            >
                                Upload logo
                            </button>
                            {logoForm.errors.logo && <p className="text-sm text-red-600">{logoForm.errors.logo}</p>}
                        </form>
                    </div>

                    <form onSubmit={submit} className="space-y-6 rounded-lg bg-white p-6 shadow">
                        <p className="text-sm text-gray-600">
                            Leave a field empty to clear your company override and fall back to the global default ({company.name}).
                        </p>

                        {keys.map((key) => (
                            <div key={key}>
                                <label className="block text-sm font-medium text-gray-700">
                                    {key}
                                    <span className="ml-2 font-normal text-gray-500">
                                        (global: {globalValues[key] === null || globalValues[key] === undefined ? '—' : String(globalValues[key])})
                                    </span>
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
                            className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                        >
                            Save settings
                        </button>
                    </form>
                </div>
            </div>
        </CompanyDashboardLayout>
    );
}
