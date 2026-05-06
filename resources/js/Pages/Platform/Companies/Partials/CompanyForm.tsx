import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { InertiaFormProps } from '@inertiajs/react';
import '@/Components/Core/css/switch.css';
import { useState } from 'react';

type Option = { value: string; label: string };

type CompanyFormData = {
    name_ar: string;
    name_en: string;
    slug: string;
    logo: File | null;
    phone: string;
    email: string;
    short_description_ar: string;
    short_description_en: string;
    description_ar: string;
    description_en: string;
    status: string;
    subscription_status: string;
    subscription_starts_at: string;
    subscription_ends_at: string;
    is_featured: boolean;
    default_inspection_fee: string;
    allow_final_invoice_without_items: boolean;
    create_owner?: boolean;
    owner_user_id?: string | number;
    owner_name_ar?: string;
    owner_name_en?: string;
    owner_email?: string;
    owner_phone?: string;
    owner_status?: string;
    owner_password?: string;
    owner_password_confirmation?: string;
};

type PrimaryUser = {
    id: number;
    name: string;
    name_ar: string | null;
    name_en: string | null;
    email: string;
    phone: string | null;
    status: string;
} | null;

function FieldError({ error }: { error?: string }) {
    if (!error) return null;
    return <div className="invalid-feedback d-block">{error}</div>;
}

function RequiredMark() {
    return <span className="text-danger"> *</span>;
}

function PasswordInput({
    id,
    name,
    value,
    onChange,
    isInvalid,
    autoComplete,
    required,
}: {
    id: string;
    name: string;
    value: string;
    onChange: (v: string) => void;
    isInvalid: boolean;
    autoComplete?: string;
    required?: boolean;
}) {
    const [show, setShow] = useState(false);

    return (
        <div className="input-group">
            <input
                id={id}
                name={name}
                type={show ? 'text' : 'password'}
                className={`form-control ${isInvalid ? 'is-invalid' : ''}`}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                dir="ltr"
                autoComplete={autoComplete}
            />
            <div className="input-group-append">
                <button
                    type="button"
                    className="btn btn-outline-secondary"
                    onClick={() => setShow((s) => !s)}
                    aria-label={show ? 'Hide password' : 'Show password'}
                >
                    <i className={`fas ${show ? 'fa-eye-slash' : 'fa-eye'}`} />
                </button>
            </div>
            {required ? null : null}
        </div>
    );
}

export default function CompanyForm({
    form,
    mode,
    enums,
    logoUrl,
    clientRequiredErrors,
    primaryUser,
    userStatuses,
}: {
    form: InertiaFormProps<CompanyFormData>;
    mode: 'create' | 'edit';
    enums: { company_status: Option[]; subscription_status: Option[] };
    logoUrl?: string | null;
    clientRequiredErrors?: Record<string, boolean>;
    primaryUser?: PrimaryUser;
    userStatuses?: Option[];
}) {
    const { data, setData, errors, processing } = form;
    const anyErrors = errors as Record<string, string | undefined>;
    const requiredClient = clientRequiredErrors ?? {};

    const isInvalid = (key: keyof CompanyFormData) => Boolean((errors as any)?.[key] || requiredClient[String(key)]);
    const fieldError = (key: keyof CompanyFormData) => {
        const server = (errors as any)?.[key] as string | undefined;
        if (server) return server;
        return requiredClient[String(key)] ? __('tenant.field_required') : undefined;
    };
    const hasPrimaryUser = Boolean(primaryUser && primaryUser.id);
    const showOwnerFields =
        mode === 'edit' ? (hasPrimaryUser ? true : Boolean(data.create_owner)) : Boolean(data.create_owner);

    return (
        <div className="card card-outline card-primary">
            <div className="card-body">
                <div className="row">
                    <div className="col-12 col-lg-6">
                        <h5 className="mb-3">{__('tenant.companies.companyInfoSection')}</h5>

                        <div className="form-group">
                            <label htmlFor="name_ar">
                                {__('tenant.companies.nameAr')}
                                <RequiredMark />
                            </label>
                            <input
                                id="name_ar"
                                name="name_ar"
                                className={`form-control ${isInvalid('name_ar') ? 'is-invalid' : ''}`}
                                value={data.name_ar}
                                onChange={(e) => setData('name_ar', e.target.value)}
                                dir="rtl"
                            />
                            <FieldError error={fieldError('name_ar')} />
                        </div>

                        <div className="form-group">
                            <label htmlFor="name_en">{__('tenant.companies.nameEn')}</label>
                            <input
                                id="name_en"
                                name="name_en"
                                className={`form-control ${isInvalid('name_en') ? 'is-invalid' : ''}`}
                                value={data.name_en}
                                onChange={(e) => setData('name_en', e.target.value)}
                                dir="ltr"
                            />
                            <FieldError error={errors.name_en} />
                        </div>

                        <div className="form-group">
                            <label htmlFor="slug">{__('tenant.companies.slug')}</label>
                            <input
                                id="slug"
                                name="slug"
                                className={`form-control ${isInvalid('slug') ? 'is-invalid' : ''}`}
                                value={data.slug}
                                onChange={(e) => setData('slug', e.target.value)}
                                dir="ltr"
                            />
                            <FieldError error={errors.slug} />
                        </div>

                        <div className="form-group">
                            <label htmlFor="logo">{__('tenant.companies.logo')}</label>
                            {logoUrl ? (
                                <div className="mb-2">
                                    <img
                                        src={logoUrl}
                                        alt=""
                                        className="rounded border bg-white"
                                        style={{ width: 72, height: 72, objectFit: 'contain' }}
                                    />
                                </div>
                            ) : null}
                            <input
                                id="logo"
                                name="logo"
                                type="file"
                                accept="image/*"
                                className={`form-control ${isInvalid('logo') ? 'is-invalid' : ''}`}
                                onChange={(e) => setData('logo', e.target.files?.[0] ?? null)}
                            />
                            <FieldError error={anyErrors.logo} />
                        </div>

                        <div className="row">
                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <label htmlFor="phone">{__('tenant.companies.phone')}</label>
                                    <input
                                        id="phone"
                                        name="phone"
                                        className={`form-control ${isInvalid('phone') ? 'is-invalid' : ''}`}
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        dir="ltr"
                                    />
                                    <FieldError error={errors.phone} />
                                </div>
                            </div>
                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <label htmlFor="email">{__('tenant.companies.email')}</label>
                                    <input
                                        id="email"
                                        name="email"
                                        className={`form-control ${isInvalid('email') ? 'is-invalid' : ''}`}
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        dir="ltr"
                                    />
                                    <FieldError error={errors.email} />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="col-12 col-lg-6">
                        <h5 className="mb-3">{__('tenant.companies.subscriptionSection')}</h5>

                        <div className="row">
                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <label htmlFor="status">
                                        {__('tenant.companies.status')}
                                        <RequiredMark />
                                    </label>
                                    <select
                                        id="status"
                                        name="status"
                                        className={`custom-select ${isInvalid('status') ? 'is-invalid' : ''}`}
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value)}
                                    >
                                        {enums.company_status.map((o) => (
                                            <option key={o.value} value={o.value}>
                                                {o.label}
                                            </option>
                                        ))}
                                    </select>
                                    <FieldError error={fieldError('status')} />
                                </div>
                            </div>

                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <label htmlFor="subscription_status">
                                        {__('tenant.companies.subscriptionStatus')}
                                        <RequiredMark />
                                    </label>
                                    <select
                                        id="subscription_status"
                                        name="subscription_status"
                                        className={`custom-select ${isInvalid('subscription_status') ? 'is-invalid' : ''}`}
                                        value={data.subscription_status}
                                        onChange={(e) => setData('subscription_status', e.target.value)}
                                    >
                                        {enums.subscription_status.map((o) => (
                                            <option key={o.value} value={o.value}>
                                                {o.label}
                                            </option>
                                        ))}
                                    </select>
                                    <FieldError error={fieldError('subscription_status')} />
                                </div>
                            </div>
                        </div>

                        <div className="row">
                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <label htmlFor="subscription_starts_at">{__('tenant.companies.subscriptionStartsAt')}</label>
                                    <input
                                        id="subscription_starts_at"
                                        name="subscription_starts_at"
                                        type="date"
                                        className={`form-control ${isInvalid('subscription_starts_at') ? 'is-invalid' : ''}`}
                                        value={data.subscription_starts_at}
                                        onChange={(e) => setData('subscription_starts_at', e.target.value)}
                                        dir="ltr"
                                    />
                                    <FieldError error={errors.subscription_starts_at} />
                                </div>
                            </div>
                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <label htmlFor="subscription_ends_at">{__('tenant.companies.subscriptionEndsAt')}</label>
                                    <input
                                        id="subscription_ends_at"
                                        name="subscription_ends_at"
                                        type="date"
                                        className={`form-control ${isInvalid('subscription_ends_at') ? 'is-invalid' : ''}`}
                                        value={data.subscription_ends_at}
                                        onChange={(e) => setData('subscription_ends_at', e.target.value)}
                                        dir="ltr"
                                    />
                                    <FieldError error={errors.subscription_ends_at} />
                                </div>
                            </div>
                        </div>

                        <div className="row">
                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <label htmlFor="default_inspection_fee">{__('tenant.companies.defaultInspectionFee')}</label>
                                    <input
                                        id="default_inspection_fee"
                                        name="default_inspection_fee"
                                        className={`form-control ${isInvalid('default_inspection_fee') ? 'is-invalid' : ''}`}
                                        value={data.default_inspection_fee}
                                        onChange={(e) => setData('default_inspection_fee', e.target.value)}
                                        dir="ltr"
                                    />
                                    <FieldError error={errors.default_inspection_fee} />
                                </div>
                            </div>
                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <div className="custom-control custom-switch custom-switch-md">
                                        <input
                                            type="checkbox"
                                            className="custom-control-input"
                                            id="is_featured"
                                            checked={data.is_featured}
                                            onChange={(e) => setData('is_featured', e.target.checked)}
                                        />
                                        <label className="custom-control-label pt-1 pl-3" htmlFor="is_featured">
                                            {__('tenant.companies.isFeatured')}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="form-group">
                            <div className="custom-control custom-switch custom-switch-md">
                                <input
                                    type="checkbox"
                                    className="custom-control-input"
                                    id="allow_final_invoice_without_items"
                                    checked={data.allow_final_invoice_without_items}
                                    onChange={(e) => setData('allow_final_invoice_without_items', e.target.checked)}
                                />
                                <label className="custom-control-label pt-1 pl-3" htmlFor="allow_final_invoice_without_items">
                                    {__('tenant.companies.allowFinalInvoiceWithoutItems')}
                                </label>
                            </div>
                            <FieldError error={errors.allow_final_invoice_without_items} />
                        </div>
                    </div>
                </div>

                <hr className="my-4" />

                <h5 className="mb-3">{__('tenant.companies.descriptionSection')}</h5>
                <div className="row">
                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="short_description_ar">{__('tenant.companies.shortDescriptionAr')}</label>
                            <textarea
                                id="short_description_ar"
                                name="short_description_ar"
                                className={`form-control ${isInvalid('short_description_ar') ? 'is-invalid' : ''}`}
                                value={data.short_description_ar}
                                onChange={(e) => setData('short_description_ar', e.target.value)}
                                dir="rtl"
                                rows={2}
                            />
                            <FieldError error={errors.short_description_ar} />
                        </div>
                        <div className="form-group">
                            <label htmlFor="description_ar">{__('tenant.companies.descriptionAr')}</label>
                            <textarea
                                id="description_ar"
                                name="description_ar"
                                className={`form-control ${isInvalid('description_ar') ? 'is-invalid' : ''}`}
                                value={data.description_ar}
                                onChange={(e) => setData('description_ar', e.target.value)}
                                dir="rtl"
                                rows={4}
                            />
                            <FieldError error={errors.description_ar} />
                        </div>
                    </div>
                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="short_description_en">{__('tenant.companies.shortDescriptionEn')}</label>
                            <textarea
                                id="short_description_en"
                                name="short_description_en"
                                className={`form-control ${isInvalid('short_description_en') ? 'is-invalid' : ''}`}
                                value={data.short_description_en}
                                onChange={(e) => setData('short_description_en', e.target.value)}
                                dir="ltr"
                                rows={2}
                            />
                            <FieldError error={errors.short_description_en} />
                        </div>
                        <div className="form-group">
                            <label htmlFor="description_en">{__('tenant.companies.descriptionEn')}</label>
                            <textarea
                                id="description_en"
                                name="description_en"
                                className={`form-control ${isInvalid('description_en') ? 'is-invalid' : ''}`}
                                value={data.description_en}
                                onChange={(e) => setData('description_en', e.target.value)}
                                dir="ltr"
                                rows={4}
                            />
                            <FieldError error={errors.description_en} />
                        </div>
                    </div>
                </div>

                <hr className="my-4" />

                <div className="card card-outline card-info mb-0">
                    <div className="card-header">
                        <h5 className="card-title m-0">{__('tenant.companies.primaryUser.sectionTitle')}</h5>
                    </div>
                    <div className="card-body">
                        <div className="alert alert-info py-2">
                            {__('tenant.companies.primaryUser.sectionHint')}
                        </div>

                        {mode === 'edit' && !hasPrimaryUser ? (
                            <div className="alert alert-warning py-2">
                                {__('tenant.companies.primaryUser.noPrimaryUser')}
                            </div>
                        ) : null}

                        <div className="form-group mb-3">
                            <div className="custom-control custom-switch custom-switch-md">
                                <input
                                    type="checkbox"
                                    className="custom-control-input"
                                    id="create_owner"
                                    checked={Boolean(data.create_owner)}
                                    onChange={(e) => setData('create_owner', e.target.checked)}
                                />
                                <label className="custom-control-label pt-1 pl-3" htmlFor="create_owner">
                                    {__('tenant.companies.primaryUser.createOwner')}
                                </label>
                            </div>

                            {mode === 'create' && !data.create_owner ? (
                                <div className="text-muted small mt-2">{__('tenant.companies.primaryUser.createOwnerOffHint')}</div>
                            ) : null}
                        </div>

                        {hasPrimaryUser ? <input type="hidden" name="owner_user_id" value={String(data.owner_user_id ?? '')} /> : null}

                        {showOwnerFields ? (
                            <>
                                <div className="row">
                                    <div className="col-12 col-lg-6">
                                        <div className="form-group">
                                            <label htmlFor="owner_name_ar">
                                                {__('tenant.companies.primaryUser.ownerNameAr')}
                                                {Boolean(data.create_owner) ? <RequiredMark /> : null}
                                            </label>
                                            <input
                                                id="owner_name_ar"
                                                name="owner_name_ar"
                                                className={`form-control ${requiredClient.owner_name_ar || anyErrors.owner_name_ar ? 'is-invalid' : ''}`}
                                                value={data.owner_name_ar ?? ''}
                                                onChange={(e) => setData('owner_name_ar', e.target.value)}
                                                dir="rtl"
                                            />
                                            <FieldError error={anyErrors.owner_name_ar} />
                                        </div>

                                        <div className="form-group">
                                            <label htmlFor="owner_email">
                                                {__('tenant.companies.primaryUser.ownerEmail')}
                                                {Boolean(data.create_owner) ? <RequiredMark /> : null}
                                            </label>
                                            <input
                                                id="owner_email"
                                                name="owner_email"
                                                className={`form-control ${requiredClient.owner_email || anyErrors.owner_email ? 'is-invalid' : ''}`}
                                                value={data.owner_email ?? ''}
                                                onChange={(e) => setData('owner_email', e.target.value)}
                                                dir="ltr"
                                            />
                                            <FieldError error={anyErrors.owner_email} />
                                        </div>

                                        <div className="form-group">
                                            <label htmlFor="owner_phone">{__('tenant.companies.primaryUser.ownerPhone')}</label>
                                            <input
                                                id="owner_phone"
                                                name="owner_phone"
                                                className={`form-control ${requiredClient.owner_phone || anyErrors.owner_phone ? 'is-invalid' : ''}`}
                                                value={data.owner_phone ?? ''}
                                                onChange={(e) => setData('owner_phone', e.target.value)}
                                                dir="ltr"
                                            />
                                            <FieldError error={anyErrors.owner_phone} />
                                        </div>

                                        {mode === 'edit' && hasPrimaryUser ? (
                                            <div className="form-group">
                                                <label htmlFor="owner_status">{__('tenant.companies.primaryUser.ownerStatus')}</label>
                                                <select
                                                    id="owner_status"
                                                    name="owner_status"
                                                    className={`custom-select ${requiredClient.owner_status || anyErrors.owner_status ? 'is-invalid' : ''}`}
                                                    value={data.owner_status ?? 'active'}
                                                    onChange={(e) => setData('owner_status', e.target.value)}
                                                >
                                                    {(userStatuses ?? []).map((o) => (
                                                        <option key={o.value} value={o.value}>
                                                            {o.label}
                                                        </option>
                                                    ))}
                                                </select>
                                                <FieldError error={anyErrors.owner_status} />
                                            </div>
                                        ) : null}
                                    </div>

                                    <div className="col-12 col-lg-6">
                                        <div className="form-group">
                                            <label htmlFor="owner_name_en">{__('tenant.companies.primaryUser.ownerNameEn')}</label>
                                            <input
                                                id="owner_name_en"
                                                name="owner_name_en"
                                                className={`form-control ${requiredClient.owner_name_en || anyErrors.owner_name_en ? 'is-invalid' : ''}`}
                                                value={data.owner_name_en ?? ''}
                                                onChange={(e) => setData('owner_name_en', e.target.value)}
                                                dir="ltr"
                                            />
                                            <FieldError error={anyErrors.owner_name_en} />
                                        </div>

                                        <div className="form-group">
                                            <label htmlFor="owner_password">
                                                {__('tenant.companies.primaryUser.ownerPassword')}
                                                {Boolean(data.create_owner) && (!hasPrimaryUser || mode === 'create') ? <RequiredMark /> : null}
                                            </label>
                                            {mode === 'edit' ? (
                                                <div className="text-muted small mb-1">{__('tenant.companies.primaryUser.passwordEditHint')}</div>
                                            ) : null}
                                            <PasswordInput
                                                id="owner_password"
                                                name="owner_password"
                                                value={data.owner_password ?? ''}
                                                onChange={(v) => setData('owner_password', v)}
                                                isInvalid={Boolean(requiredClient.owner_password || anyErrors.owner_password)}
                                                autoComplete="new-password"
                                                required={Boolean(data.create_owner) && (!hasPrimaryUser || mode === 'create')}
                                            />
                                            <FieldError error={anyErrors.owner_password} />
                                        </div>

                                        <div className="form-group">
                                            <label htmlFor="owner_password_confirmation">
                                                {__('tenant.companies.primaryUser.ownerPasswordConfirmation')}
                                                {Boolean(data.create_owner) && (!hasPrimaryUser || mode === 'create') ? <RequiredMark /> : null}
                                            </label>
                                            <PasswordInput
                                                id="owner_password_confirmation"
                                                name="owner_password_confirmation"
                                                value={data.owner_password_confirmation ?? ''}
                                                onChange={(v) => setData('owner_password_confirmation', v)}
                                                isInvalid={Boolean(requiredClient.owner_password_confirmation || anyErrors.owner_password_confirmation)}
                                                autoComplete="new-password"
                                                required={Boolean(data.create_owner) && (!hasPrimaryUser || mode === 'create')}
                                            />
                                            <FieldError error={anyErrors.owner_password_confirmation} />
                                        </div>
                                    </div>
                                </div>
                            </>
                        ) : null}
                    </div>
                </div>

                <div className="d-flex align-items-center justify-content-end mt-4">
                    <button type="submit" className="btn btn-primary" disabled={processing}>
                        {mode === 'create' ? __('tenant.companies.save') : __('tenant.companies.update')}
                    </button>
                </div>
            </div>
        </div>
    );
}

