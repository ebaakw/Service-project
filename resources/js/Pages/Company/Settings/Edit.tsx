import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';

type CompanyProfileForm = {
    name_ar: string;
    name_en: string;
    short_description_ar: string;
    short_description_en: string;
};

type SlaForm = {
    technician_accept_sla_minutes: string;
    technician_arrival_sla_minutes: string;
};

type Props = PageProps & {
    company: {
        id: number;
        name: string;
        name_ar: string | null;
        name_en: string | null;
        short_description_ar: string | null;
        short_description_en: string | null;
        logo_path: string | null;
        logo_url: string | null;
    };
    sla: {
        technician_accept_sla_minutes: number | null;
        technician_arrival_sla_minutes: number | null;
        effective_accept_minutes: number;
        effective_arrival_minutes: number;
        platform_accept_minutes: number;
        platform_arrival_minutes: number;
        accept_overridden: boolean;
        arrival_overridden: boolean;
    };
};

function resolveFlashMessage(message?: string | null): string | null {
    if (!message) {
        return null;
    }

    if (message === 'Settings updated.') {
        return __('dashboard.company_settings.saved_message');
    }

    if (message === 'Logo updated.') {
        return __('dashboard.company_settings.logo_saved_message');
    }

    return message;
}

export default function CompanySettingsEdit({ company, sla }: Props) {
    const flash = (usePage().props as PageProps & { flash?: { message?: string | null } }).flash;
    const flashMessage = resolveFlashMessage(flash?.message);
    const page = usePage<PageProps>().props;
    const isRtl = page.direction === 'rtl';

    const { data, setData, put, processing, errors } = useForm<{ company: CompanyProfileForm; sla: SlaForm }>({
        company: {
            name_ar: company.name_ar ?? company.name ?? '',
            name_en: company.name_en ?? '',
            short_description_ar: company.short_description_ar ?? '',
            short_description_en: company.short_description_en ?? '',
        },
        sla: {
            technician_accept_sla_minutes:
                sla.technician_accept_sla_minutes !== null ? String(sla.technician_accept_sla_minutes) : '',
            technician_arrival_sla_minutes:
                sla.technician_arrival_sla_minutes !== null ? String(sla.technician_arrival_sla_minutes) : '',
        },
    });

    const logoForm = useForm<{ logo: File | null }>({ logo: null });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(route('company.settings.update'));
    };

    const submitLogo = (e: FormEvent) => {
        e.preventDefault();
        logoForm.post(route('company.settings.logo'), { forceFormData: true });
    };

    const updateCompany = (key: keyof CompanyProfileForm, value: string) => {
        setData('company', {
            ...data.company,
            [key]: value,
        });
    };

    const updateSla = (key: keyof SlaForm, value: string) => {
        setData('sla', {
            ...data.sla,
            [key]: value,
        });
    };

    const getFieldError = (key: keyof CompanyProfileForm): string | undefined =>
        errors[`company.${key}` as keyof typeof errors] as string | undefined;
    const getSlaError = (key: keyof SlaForm): string | undefined =>
        errors[`sla.${key}` as keyof typeof errors] as string | undefined;
    const displayCompanyName = data.company.name_ar || data.company.name_en || company.name;

    return (
        <CompanyDashboardLayout
            breadcrumbKey="dashboard.nav.company_settings"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('dashboard.company_settings.page_title')}</h4>}
        >
            <Head title={__('dashboard.company_settings.page_title')} />

            <div className="d-flex align-items-center justify-content-between flex-wrap mb-3">
                <div>
                    <h1 className="h3 mb-1 font-weight-bold text-dark">{__('dashboard.company_settings.page_title')}</h1>
                    <p className="text-muted mb-0">{__('dashboard.company_settings.page_description')}</p>
                </div>
                <Link href={route('company.dashboard')} className="btn btn-outline-secondary btn-sm mt-2 mt-md-0">
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

            <div className="row">
                <div className="col-12 col-xl-4">
                    <div className="card card-outline card-success h-100">
                        <div className="card-header">
                            <h3 className="card-title mb-0 font-weight-bold">
                                <i className="fas fa-building mr-2 text-success" />
                                {__('dashboard.company_settings.identity_title')}
                            </h3>
                        </div>
                        <div className="card-body">
                            <div className="d-flex align-items-center mb-4">
                                <div className="bg-light border rounded d-flex align-items-center justify-content-center mr-3" style={{ width: 72, height: 72 }}>
                                    {company.logo_url ? (
                                        <img
                                            src={company.logo_url}
                                            alt={__('dashboard.company_settings.logo_alt')}
                                            className="img-fluid p-1"
                                            style={{ maxHeight: 68, objectFit: 'contain' }}
                                        />
                                    ) : (
                                        <i className="fas fa-image text-muted fa-2x" aria-hidden="true" />
                                    )}
                                </div>
                                <div>
                                    <div className="text-muted small text-uppercase font-weight-bold">
                                        {__('dashboard.company_settings.company_label')}
                                    </div>
                                    <div className="h5 mb-1 font-weight-bold text-dark">{displayCompanyName}</div>
                                    <div className={company.logo_url ? 'badge badge-success' : 'badge badge-light border'}>
                                        {company.logo_url
                                            ? __('dashboard.company_settings.logo_ready')
                                            : __('dashboard.company_settings.logo_missing')}
                                    </div>
                                </div>
                            </div>

                            <p className="text-muted small">{__('dashboard.company_settings.logo_help')}</p>

                            <form onSubmit={submitLogo} noValidate>
                                <div className="form-group">
                                    <label htmlFor="company-logo" className="font-weight-bold">
                                        {__('dashboard.company_settings.logo_label')}
                                    </label>
                                    <input
                                        id="company-logo"
                                        type="file"
                                        accept="image/*"
                                        className={`form-control-file ${logoForm.errors.logo ? 'is-invalid' : ''}`}
                                        onChange={(e) => logoForm.setData('logo', e.target.files?.[0] ?? null)}
                                    />
                                    {logoForm.errors.logo ? <div className="invalid-feedback d-block">{logoForm.errors.logo}</div> : null}
                                </div>

                                <button
                                    type="submit"
                                    disabled={logoForm.processing || !logoForm.data.logo}
                                    className="btn btn-success btn-block"
                                >
                                    {logoForm.processing ? (
                                        <>
                                            <i className="fas fa-spinner fa-spin mr-1" />
                                            {__('dashboard.company_settings.uploading_logo')}
                                        </>
                                    ) : (
                                        <>
                                            <i className="fas fa-upload mr-1" />
                                            {__('dashboard.company_settings.upload_logo')}
                                        </>
                                    )}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div className="col-12 col-xl-8">
                    <form onSubmit={submit} noValidate>
                        <div className="card card-outline card-primary">
                            <div className="card-header">
                                <h3 className="card-title mb-0 font-weight-bold">
                                    <i className="fas fa-id-card mr-2 text-primary" />
                                    {__('dashboard.company_settings.profile_title')}
                                </h3>
                            </div>
                            <div className="card-body">
                                <div className="alert alert-light border d-flex align-items-start" role="note">
                                    <i className="fas fa-info-circle text-primary mr-2 mt-1" />
                                    <div>
                                        <div className="font-weight-bold">{__('dashboard.company_settings.profile_hint_title')}</div>
                                        <div className="small text-muted">
                                            {__('dashboard.company_settings.profile_hint')}
                                        </div>
                                    </div>
                                </div>

                                <div className="row">
                                    <div className="col-12 col-lg-6">
                                        <div className="form-group">
                                            <label htmlFor="company-name-ar" className="font-weight-bold">
                                                {__('dashboard.company_settings.fields.name_ar.label')}
                                                <span className="text-danger"> *</span>
                                            </label>
                                            <div className="text-muted small mb-2">
                                                {__('dashboard.company_settings.fields.name_ar.description')}
                                            </div>
                                            <input
                                                id="company-name-ar"
                                                name="company.name_ar"
                                                className={`form-control ${getFieldError('name_ar') ? 'is-invalid' : ''}`}
                                                value={data.company.name_ar}
                                                onChange={(e) => updateCompany('name_ar', e.target.value)}
                                                dir="rtl"
                                            />
                                            {getFieldError('name_ar') ? <div className="invalid-feedback d-block">{getFieldError('name_ar')}</div> : null}
                                        </div>
                                    </div>

                                    <div className="col-12 col-lg-6">
                                        <div className="form-group">
                                            <label htmlFor="company-name-en" className="font-weight-bold">
                                                {__('dashboard.company_settings.fields.name_en.label')}
                                            </label>
                                            <div className="text-muted small mb-2">
                                                {__('dashboard.company_settings.fields.name_en.description')}
                                            </div>
                                            <input
                                                id="company-name-en"
                                                name="company.name_en"
                                                className={`form-control ${getFieldError('name_en') ? 'is-invalid' : ''}`}
                                                value={data.company.name_en}
                                                onChange={(e) => updateCompany('name_en', e.target.value)}
                                                dir="ltr"
                                            />
                                            {getFieldError('name_en') ? <div className="invalid-feedback d-block">{getFieldError('name_en')}</div> : null}
                                        </div>
                                    </div>

                                    <div className="col-12 col-lg-6">
                                        <div className="form-group mb-lg-0">
                                            <label htmlFor="company-short-description-ar" className="font-weight-bold">
                                                {__('dashboard.company_settings.fields.short_description_ar.label')}
                                            </label>
                                            <div className="text-muted small mb-2">
                                                {__('dashboard.company_settings.fields.short_description_ar.description')}
                                            </div>
                                            <textarea
                                                id="company-short-description-ar"
                                                name="company.short_description_ar"
                                                className={`form-control ${getFieldError('short_description_ar') ? 'is-invalid' : ''}`}
                                                value={data.company.short_description_ar}
                                                onChange={(e) => updateCompany('short_description_ar', e.target.value)}
                                                dir="rtl"
                                                rows={4}
                                            />
                                            {getFieldError('short_description_ar') ? <div className="invalid-feedback d-block">{getFieldError('short_description_ar')}</div> : null}
                                        </div>
                                    </div>

                                    <div className="col-12 col-lg-6">
                                        <div className="form-group mb-0">
                                            <label htmlFor="company-short-description-en" className="font-weight-bold">
                                                {__('dashboard.company_settings.fields.short_description_en.label')}
                                            </label>
                                            <div className="text-muted small mb-2">
                                                {__('dashboard.company_settings.fields.short_description_en.description')}
                                            </div>
                                            <textarea
                                                id="company-short-description-en"
                                                name="company.short_description_en"
                                                className={`form-control ${getFieldError('short_description_en') ? 'is-invalid' : ''}`}
                                                value={data.company.short_description_en}
                                                onChange={(e) => updateCompany('short_description_en', e.target.value)}
                                                dir="ltr"
                                                rows={4}
                                            />
                                            {getFieldError('short_description_en') ? <div className="invalid-feedback d-block">{getFieldError('short_description_en')}</div> : null}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="card-footer d-flex align-items-center justify-content-between flex-wrap">
                                <div className="text-muted mb-3 mb-md-0">
                                    <i className="fas fa-shield-alt mr-1" />
                                    {__('dashboard.company_settings.save_hint')}
                                </div>
                                <button type="submit" disabled={processing} className="btn btn-primary px-4">
                                    {processing ? (
                                        <>
                                            <i className="fas fa-spinner fa-spin mr-1" />
                                            {__('dashboard.company_settings.saving')}
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

                        <div className="card card-outline card-warning mt-3">
                            <div className="card-header">
                                <h3 className="card-title mb-0 font-weight-bold">
                                    <i className="fas fa-stopwatch mr-2 text-warning" />
                                    {__('dashboard.company_settings.sla_title')}
                                </h3>
                            </div>
                            <div className="card-body">
                                <div className="alert alert-light border d-flex align-items-start" role="note">
                                    <i className="fas fa-info-circle text-warning mr-2 mt-1" />
                                    <div>
                                        <div className="font-weight-bold">
                                            {__('dashboard.company_settings.sla_hint_title')}
                                        </div>
                                        <div className="small text-muted">
                                            {__('dashboard.company_settings.sla_hint')}
                                        </div>
                                        <div className="small text-muted mt-1" dir="ltr">
                                            {__('dashboard.company_settings.sla_platform_defaults')}:{' '}
                                            {sla.platform_accept_minutes} / {sla.platform_arrival_minutes}{' '}
                                            {__('dashboard.company_settings.sla_minutes')}
                                        </div>
                                    </div>
                                </div>

                                <div className="row">
                                    <div className="col-12 col-lg-6">
                                        <div className="form-group mb-lg-0">
                                            <label htmlFor="sla-accept" className="font-weight-bold">
                                                {__('dashboard.company_settings.fields.technician_accept_sla_minutes.label')}
                                            </label>
                                            <div className="text-muted small mb-2">
                                                {__(
                                                    'dashboard.company_settings.fields.technician_accept_sla_minutes.description',
                                                )}
                                            </div>
                                            <input
                                                id="sla-accept"
                                                name="sla.technician_accept_sla_minutes"
                                                type="number"
                                                min={1}
                                                step={1}
                                                className={`form-control ${getSlaError('technician_accept_sla_minutes') ? 'is-invalid' : ''}`}
                                                value={data.sla.technician_accept_sla_minutes}
                                                onChange={(e) => updateSla('technician_accept_sla_minutes', e.target.value)}
                                                placeholder={String(sla.platform_accept_minutes)}
                                                aria-label={__(
                                                    'dashboard.company_settings.fields.technician_accept_sla_minutes.label',
                                                )}
                                                dir="ltr"
                                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                                            />
                                            {getSlaError('technician_accept_sla_minutes') ? (
                                                <div className="invalid-feedback d-block">
                                                    {getSlaError('technician_accept_sla_minutes')}
                                                </div>
                                            ) : (
                                                <div className="small text-muted mt-1">
                                                    {__('dashboard.company_settings.sla_effective')}:{' '}
                                                    {sla.effective_accept_minutes}{' '}
                                                    {__('dashboard.company_settings.sla_minutes')}
                                                    {sla.accept_overridden
                                                        ? ` (${__('dashboard.company_settings.sla_custom')})`
                                                        : ` (${__('dashboard.company_settings.sla_from_platform')})`}
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    <div className="col-12 col-lg-6">
                                        <div className="form-group mb-0">
                                            <label htmlFor="sla-arrival" className="font-weight-bold">
                                                {__('dashboard.company_settings.fields.technician_arrival_sla_minutes.label')}
                                            </label>
                                            <div className="text-muted small mb-2">
                                                {__(
                                                    'dashboard.company_settings.fields.technician_arrival_sla_minutes.description',
                                                )}
                                            </div>
                                            <input
                                                id="sla-arrival"
                                                name="sla.technician_arrival_sla_minutes"
                                                type="number"
                                                min={1}
                                                step={1}
                                                className={`form-control ${getSlaError('technician_arrival_sla_minutes') ? 'is-invalid' : ''}`}
                                                value={data.sla.technician_arrival_sla_minutes}
                                                onChange={(e) => updateSla('technician_arrival_sla_minutes', e.target.value)}
                                                placeholder={String(sla.platform_arrival_minutes)}
                                                aria-label={__(
                                                    'dashboard.company_settings.fields.technician_arrival_sla_minutes.label',
                                                )}
                                                dir="ltr"
                                                style={{ textAlign: isRtl ? 'right' : 'left' }}
                                            />
                                            {getSlaError('technician_arrival_sla_minutes') ? (
                                                <div className="invalid-feedback d-block">
                                                    {getSlaError('technician_arrival_sla_minutes')}
                                                </div>
                                            ) : (
                                                <div className="small text-muted mt-1">
                                                    {__('dashboard.company_settings.sla_effective')}:{' '}
                                                    {sla.effective_arrival_minutes}{' '}
                                                    {__('dashboard.company_settings.sla_minutes')}
                                                    {sla.arrival_overridden
                                                        ? ` (${__('dashboard.company_settings.sla_custom')})`
                                                        : ` (${__('dashboard.company_settings.sla_from_platform')})`}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="card-footer d-flex align-items-center justify-content-between flex-wrap">
                                <div className="text-muted mb-3 mb-md-0">
                                    <i className="fas fa-undo mr-1" />
                                    {__('dashboard.company_settings.sla_empty_hint')}
                                </div>
                                <button type="submit" disabled={processing} className="btn btn-warning px-4">
                                    {processing ? (
                                        <>
                                            <i className="fas fa-spinner fa-spin mr-1" />
                                            {__('dashboard.company_settings.saving')}
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
                </div>
            </div>
        </CompanyDashboardLayout>
    );
}
