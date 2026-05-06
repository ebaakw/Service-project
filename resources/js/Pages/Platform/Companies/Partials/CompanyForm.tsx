import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { InertiaFormProps } from '@inertiajs/react';
import '@/Components/Core/css/switch.css';

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
    owner_name_ar?: string;
    owner_name_en?: string;
    owner_email?: string;
    owner_phone?: string;
    owner_password?: string;
    owner_password_confirmation?: string;
};

function FieldError({ error }: { error?: string }) {
    if (!error) return null;
    return <div className="text-danger small mt-1">{error}</div>;
}

export default function CompanyForm({
    form,
    mode,
    enums,
    logoUrl,
}: {
    form: InertiaFormProps<CompanyFormData>;
    mode: 'create' | 'edit';
    enums: { company_status: Option[]; subscription_status: Option[] };
    logoUrl?: string | null;
}) {
    const { data, setData, errors, processing } = form;
    const anyErrors = errors as Record<string, string | undefined>;

    return (
        <div className="card card-outline card-primary">
            <div className="card-body">
                <div className="row">
                    <div className="col-12 col-lg-6">
                        <h5 className="mb-3">{__('tenant.companies.companyInfoSection')}</h5>

                        <div className="form-group">
                            <label>{__('tenant.companies.nameAr')} *</label>
                            <input
                                className="form-control"
                                value={data.name_ar}
                                onChange={(e) => setData('name_ar', e.target.value)}
                                dir="rtl"
                            />
                            <FieldError error={errors.name_ar} />
                        </div>

                        <div className="form-group">
                            <label>{__('tenant.companies.nameEn')}</label>
                            <input
                                className="form-control"
                                value={data.name_en}
                                onChange={(e) => setData('name_en', e.target.value)}
                                dir="ltr"
                            />
                            <FieldError error={errors.name_en} />
                        </div>

                        <div className="form-group">
                            <label>{__('tenant.companies.slug')}</label>
                            <input
                                className="form-control"
                                value={data.slug}
                                onChange={(e) => setData('slug', e.target.value)}
                                dir="ltr"
                            />
                            <FieldError error={errors.slug} />
                        </div>

                        <div className="form-group">
                            <label>{__('tenant.companies.logo')}</label>
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
                                type="file"
                                accept="image/*"
                                className="form-control"
                                onChange={(e) => setData('logo', e.target.files?.[0] ?? null)}
                            />
                            <FieldError error={anyErrors.logo} />
                        </div>

                        <div className="row">
                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <label>{__('tenant.companies.phone')}</label>
                                    <input
                                        className="form-control"
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        dir="ltr"
                                    />
                                    <FieldError error={errors.phone} />
                                </div>
                            </div>
                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <label>{__('tenant.companies.email')}</label>
                                    <input
                                        className="form-control"
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
                                    <label>{__('tenant.companies.status')} *</label>
                                    <select
                                        className="custom-select"
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value)}
                                    >
                                        {enums.company_status.map((o) => (
                                            <option key={o.value} value={o.value}>
                                                {o.label}
                                            </option>
                                        ))}
                                    </select>
                                    <FieldError error={errors.status} />
                                </div>
                            </div>

                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <label>{__('tenant.companies.subscriptionStatus')} *</label>
                                    <select
                                        className="custom-select"
                                        value={data.subscription_status}
                                        onChange={(e) => setData('subscription_status', e.target.value)}
                                    >
                                        {enums.subscription_status.map((o) => (
                                            <option key={o.value} value={o.value}>
                                                {o.label}
                                            </option>
                                        ))}
                                    </select>
                                    <FieldError error={errors.subscription_status} />
                                </div>
                            </div>
                        </div>

                        <div className="row">
                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <label>{__('tenant.companies.subscriptionStartsAt')}</label>
                                    <input
                                        type="date"
                                        className="form-control"
                                        value={data.subscription_starts_at}
                                        onChange={(e) => setData('subscription_starts_at', e.target.value)}
                                        dir="ltr"
                                    />
                                    <FieldError error={errors.subscription_starts_at} />
                                </div>
                            </div>
                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <label>{__('tenant.companies.subscriptionEndsAt')}</label>
                                    <input
                                        type="date"
                                        className="form-control"
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
                                    <label>{__('tenant.companies.defaultInspectionFee')}</label>
                                    <input
                                        className="form-control"
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
                            <label>{__('tenant.companies.shortDescriptionAr')}</label>
                            <textarea
                                className="form-control"
                                value={data.short_description_ar}
                                onChange={(e) => setData('short_description_ar', e.target.value)}
                                dir="rtl"
                                rows={2}
                            />
                            <FieldError error={errors.short_description_ar} />
                        </div>
                        <div className="form-group">
                            <label>{__('tenant.companies.descriptionAr')}</label>
                            <textarea
                                className="form-control"
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
                            <label>{__('tenant.companies.shortDescriptionEn')}</label>
                            <textarea
                                className="form-control"
                                value={data.short_description_en}
                                onChange={(e) => setData('short_description_en', e.target.value)}
                                dir="ltr"
                                rows={2}
                            />
                            <FieldError error={errors.short_description_en} />
                        </div>
                        <div className="form-group">
                            <label>{__('tenant.companies.descriptionEn')}</label>
                            <textarea
                                className="form-control"
                                value={data.description_en}
                                onChange={(e) => setData('description_en', e.target.value)}
                                dir="ltr"
                                rows={4}
                            />
                            <FieldError error={errors.description_en} />
                        </div>
                    </div>
                </div>

                {mode === 'create' ? (
                    <>
                        <hr className="my-4" />
                        <h5 className="mb-2">{__('tenant.companies.ownerSection')}</h5>
                        <p className="text-muted small mb-3">{__('tenant.companies.ownerLoginHint')}</p>

                        <div className="row">
                            <div className="col-12 col-lg-6">
                                <div className="form-group">
                                    <label>{__('tenant.companies.ownerNameAr')}</label>
                                    <input
                                        className="form-control"
                                        value={data.owner_name_ar ?? ''}
                                        onChange={(e) => setData('owner_name_ar', e.target.value)}
                                        dir="rtl"
                                    />
                                    <FieldError error={anyErrors.owner_name_ar} />
                                </div>
                                <div className="form-group">
                                    <label>{__('tenant.companies.ownerEmail')}</label>
                                    <input
                                        className="form-control"
                                        value={data.owner_email ?? ''}
                                        onChange={(e) => setData('owner_email', e.target.value)}
                                        dir="ltr"
                                    />
                                    <FieldError error={anyErrors.owner_email} />
                                </div>
                                <div className="form-group">
                                    <label>{__('tenant.companies.ownerPhone')}</label>
                                    <input
                                        className="form-control"
                                        value={data.owner_phone ?? ''}
                                        onChange={(e) => setData('owner_phone', e.target.value)}
                                        dir="ltr"
                                    />
                                    <FieldError error={anyErrors.owner_phone} />
                                </div>
                            </div>
                            <div className="col-12 col-lg-6">
                                <div className="form-group">
                                    <label>{__('tenant.companies.ownerNameEn')}</label>
                                    <input
                                        className="form-control"
                                        value={data.owner_name_en ?? ''}
                                        onChange={(e) => setData('owner_name_en', e.target.value)}
                                        dir="ltr"
                                    />
                                    <FieldError error={anyErrors.owner_name_en} />
                                </div>
                                <div className="form-group">
                                    <label>{__('tenant.companies.ownerPassword')}</label>
                                    <input
                                        type="password"
                                        className="form-control"
                                        value={data.owner_password ?? ''}
                                        onChange={(e) => setData('owner_password', e.target.value)}
                                        dir="ltr"
                                        autoComplete="new-password"
                                    />
                                    <FieldError error={anyErrors.owner_password} />
                                </div>
                                <div className="form-group">
                                    <label>{__('tenant.companies.ownerPasswordConfirmation')}</label>
                                    <input
                                        type="password"
                                        className="form-control"
                                        value={data.owner_password_confirmation ?? ''}
                                        onChange={(e) => setData('owner_password_confirmation', e.target.value)}
                                        dir="ltr"
                                        autoComplete="new-password"
                                    />
                                </div>
                            </div>
                        </div>
                    </>
                ) : null}

                <div className="d-flex align-items-center justify-content-end mt-4">
                    <button type="submit" className="btn btn-primary" disabled={processing}>
                        {mode === 'create' ? __('tenant.companies.save') : __('tenant.companies.update')}
                    </button>
                </div>
            </div>
        </div>
    );
}

