import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent, HTMLInputTypeAttribute } from 'react';

type Props = PageProps & {
    values: Record<string, string | number | null>;
    keys: string[];
    meta: Record<string, { description: string | null; group: string | null; is_public?: boolean | null }>;
};

type GroupUi = {
    icon: string;
    variant: 'primary' | 'info' | 'success' | 'warning' | 'secondary';
};

const GROUP_ORDER = ['orders', 'billing', 'support', 'general'];

const GROUP_UI: Record<string, GroupUi> = {
    orders: { icon: 'fa-shopping-bag', variant: 'primary' },
    billing: { icon: 'fa-file-invoice-dollar', variant: 'success' },
    support: { icon: 'fa-headset', variant: 'info' },
    general: { icon: 'fa-sliders-h', variant: 'secondary' },
};

const INPUT_TYPES: Record<string, HTMLInputTypeAttribute> = {
    inspection_fee: 'number',
    order_offer_expiry_minutes: 'number',
    technician_accept_sla_minutes: 'number',
    technician_arrival_sla_minutes: 'number',
    platform_commission_percentage: 'number',
    support_email: 'email',
    support_phone: 'tel',
};

const INPUT_STEPS: Record<string, string> = {
    inspection_fee: '0.001',
    order_offer_expiry_minutes: '1',
    technician_accept_sla_minutes: '1',
    technician_arrival_sla_minutes: '1',
    platform_commission_percentage: '0.01',
};

function humanize(value: string): string {
    return value
        .replace(/[_-]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function normalizeGroup(group?: string | null): string {
    const normalized = (group ?? '').trim();
    return normalized || 'general';
}

function getGroupUi(group: string): GroupUi {
    return GROUP_UI[group] ?? GROUP_UI.general;
}

function tr(key: string, fallback: string): string {
    const translated = __(key);
    return translated === key ? fallback : translated;
}

export default function PlatformSettingsEdit({ values, keys, meta }: Props) {
    const flash = (usePage().props as PageProps & { flash?: { message?: string | null } }).flash;
    const flashMessage =
        flash?.message === 'Global settings updated.'
            ? __('dashboard.platform_settings.saved_message')
            : flash?.message;

    const initial: Record<string, string> = {};
    keys.forEach((k) => {
        const v = values[k];
        initial[k] = v === null || v === undefined ? '' : String(v);
    });

    const { data, setData, put, processing, errors } = useForm<{ settings: Record<string, string> }>({
        settings: initial,
    });

    const groupedKeys = keys.reduce<Record<string, string[]>>((groups, key) => {
        const group = normalizeGroup(meta[key]?.group);
        groups[group] = [...(groups[group] ?? []), key];
        return groups;
    }, {});

    const groupNames = Object.keys(groupedKeys).sort((a, b) => {
        const aIndex = GROUP_ORDER.indexOf(a);
        const bIndex = GROUP_ORDER.indexOf(b);

        return (aIndex === -1 ? 99 : aIndex) - (bIndex === -1 ? 99 : bIndex) || a.localeCompare(b);
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(route('platform.settings.update'));
    };

    const updateSetting = (key: string, value: string) => {
        setData('settings', {
            ...data.settings,
            [key]: value,
        });
    };

    const getFieldError = (key: string): string | undefined => errors[`settings.${key}` as keyof typeof errors] as string | undefined;

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.platform_settings"
            header={<h1 className="m-0 font-weight-bold text-dark">{__('dashboard.platform_settings.page_title')}</h1>}
        >
            <Head title={__('dashboard.platform_settings.page_title')} />

            <div className="d-flex align-items-center justify-content-between flex-wrap mb-3">
                <div>
                    <h1 className="h3 mb-1 font-weight-bold text-dark">{__('dashboard.platform_settings.page_title')}</h1>
                    <p className="text-muted mb-0">{__('dashboard.platform_settings.page_description')}</p>
                </div>
                <Link href={route('platform.dashboard')} className="btn btn-outline-secondary btn-sm mt-2 mt-md-0">
                    <i className="fas fa-arrow-left mr-1" />
                    {__('dashboard.actions.back')}
                </Link>
            </div>

            {flashMessage ? (
                <div className="alert alert-success d-flex align-items-center" role="alert">
                    <i className="fas fa-check-circle mr-2" />
                    <span>{flashMessage}</span>
                </div>
            ) : null}

            <div className="card bg-gradient-primary text-white shadow-sm">
                <div className="card-body d-flex align-items-center justify-content-between flex-wrap">
                    <div className="mb-3 mb-md-0">
                        <div className="text-uppercase small font-weight-bold text-white-50">
                            {__('dashboard.platform_settings.hero_eyebrow')}
                        </div>
                        <h2 className="h4 mb-2">{__('dashboard.platform_settings.hero_title')}</h2>
                        <p className="mb-0 text-white-50">{__('dashboard.platform_settings.hero_description')}</p>
                    </div>
                    <div className="text-center">
                        <div className="display-4 font-weight-bold line-height-1">{keys.length}</div>
                        <div className="small text-uppercase font-weight-bold">{__('dashboard.platform_settings.settings_count')}</div>
                    </div>
                </div>
            </div>

            <form onSubmit={submit} noValidate>
                <div className="row">
                    {groupNames.map((group) => {
                        const ui = getGroupUi(group);

                        return (
                            <div className="col-12 col-xl-6" key={group}>
                                <div className={`card card-outline card-${ui.variant} h-100`}>
                                    <div className="card-header d-flex align-items-center justify-content-between">
                                        <div>
                                            <h3 className="card-title mb-0 font-weight-bold">
                                                <i className={`fas ${ui.icon} mr-2 text-${ui.variant}`} />
                                                {tr(
                                                    `dashboard.platform_settings.groups.${group}.title`,
                                                    humanize(group),
                                                )}
                                            </h3>
                                            <div className="text-muted small mt-1">
                                                {tr(
                                                    `dashboard.platform_settings.groups.${group}.description`,
                                                    __('dashboard.platform_settings.groups.general.description'),
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                    <div className="card-body">
                                        {groupedKeys[group].map((key, index) => {
                                            const fieldError = getFieldError(key);

                                            return (
                                                <div
                                                    className={index === groupedKeys[group].length - 1 ? 'form-group mb-0' : 'form-group border-bottom pb-3 mb-3'}
                                                    key={key}
                                                >
                                                    <div className="d-flex align-items-start justify-content-between flex-wrap mb-2">
                                                        <div className="pr-3">
                                                            <label htmlFor={`setting-${key}`} className="mb-1 font-weight-bold">
                                                                {tr(
                                                                    `dashboard.platform_settings.fields.${key}.label`,
                                                                    humanize(key),
                                                                )}
                                                            </label>
                                                            <div className="text-muted small">
                                                                {tr(
                                                                    `dashboard.platform_settings.fields.${key}.description`,
                                                                    meta[key]?.description ?? '',
                                                                )}
                                                            </div>
                                                        </div>
                                                        {meta[key]?.is_public ? (
                                                            <span className="badge badge-light border mt-1">
                                                                {__('dashboard.platform_settings.public_badge')}
                                                            </span>
                                                        ) : null}
                                                    </div>
                                                    <input
                                                        id={`setting-${key}`}
                                                        name={`settings.${key}`}
                                                        type={INPUT_TYPES[key] ?? 'text'}
                                                        step={INPUT_STEPS[key]}
                                                        min={INPUT_TYPES[key] === 'number' ? '0' : undefined}
                                                        className={`form-control ${fieldError ? 'is-invalid' : ''}`}
                                                        value={data.settings[key] ?? ''}
                                                        onChange={(e) => updateSetting(key, e.target.value)}
                                                        dir={key === 'support_email' || key === 'support_phone' || key === 'currency' ? 'ltr' : undefined}
                                                    />
                                                    {fieldError ? <div className="invalid-feedback d-block">{fieldError}</div> : null}
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>

                <div className="card">
                    <div className="card-body d-flex align-items-center justify-content-between flex-wrap">
                        <div className="text-muted mb-3 mb-md-0">
                            <i className="fas fa-info-circle mr-1" />
                            {__('dashboard.platform_settings.save_hint')}
                        </div>
                        <button type="submit" disabled={processing} className="btn btn-primary px-4">
                            {processing ? (
                                <>
                                    <i className="fas fa-spinner fa-spin mr-1" />
                                    {__('dashboard.platform_settings.saving')}
                                </>
                            ) : (
                                <>
                                    <i className="fas fa-save mr-1" />
                                    {__('dashboard.actions.save')}
                                </>
                            )}
                        </button>
                    </div>
                </div>
            </form>
        </PlatformAdminLayout>
    );
}
