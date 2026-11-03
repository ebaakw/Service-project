import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { InertiaFormProps } from '@inertiajs/react';
import '@/Components/Core/css/switch.css';
import { useState } from 'react';

type Option = { value: string; label: string };

type ContactRow = { name: string; phone: string; position: string };

type DocumentItem = {
    id: number;
    url: string;
    sort_order: number;
    file_name: string | null;
    mime_type: string | null;
    size: number | null;
};

type CompanyDocuments = {
    license: DocumentItem[];
    signature_authorization: DocumentItem[];
    additional_documents: DocumentItem[];
};

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
    contacts?: ContactRow[];
    address?: string;
    working_hours?: string;
    iban?: string;
    bank_name?: string;
    license_files?: File[];
    signature_authorization_files?: File[];
    additional_documents_files?: File[];
    remove_document_ids?: number[];
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

const emptyContact = (): ContactRow => ({ name: '', phone: '', position: '' });

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
    documents,
}: {
    form: InertiaFormProps<CompanyFormData>;
    mode: 'create' | 'edit';
    enums: { company_status: Option[]; subscription_status: Option[] };
    logoUrl?: string | null;
    clientRequiredErrors?: Record<string, boolean>;
    primaryUser?: PrimaryUser;
    userStatuses?: Option[];
    documents?: CompanyDocuments;
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
    const ownerFieldsRequired = Boolean(data.create_owner);
    const passwordRequired = Boolean(data.create_owner) && (!hasPrimaryUser || mode === 'create');

    const contacts = Array.isArray(data.contacts) ? data.contacts : [];
    const removeDocumentIds = Array.isArray(data.remove_document_ids) ? data.remove_document_ids : [];

    const updateContact = (index: number, key: keyof ContactRow, value: string) => {
        const next = contacts.map((row, i) => (i === index ? { ...row, [key]: value } : row));
        setData('contacts', next);
    };

    const addContact = () => setData('contacts', [...contacts, emptyContact()]);

    const removeContact = (index: number) => {
        setData(
            'contacts',
            contacts.filter((_, i) => i !== index),
        );
    };

    const markDocumentForRemoval = (id: number) => {
        if (removeDocumentIds.includes(id)) return;
        setData('remove_document_ids', [...removeDocumentIds, id]);
    };

    const visibleDocuments = (items: DocumentItem[] | undefined) =>
        (items ?? []).filter((item) => !removeDocumentIds.includes(item.id));

    const documentSections: Array<{
        key: keyof CompanyDocuments;
        filesKey: 'license_files' | 'signature_authorization_files' | 'additional_documents_files';
        labelKey: string;
    }> = [
        { key: 'license', filesKey: 'license_files', labelKey: 'tenant.companies.licenseFiles' },
        {
            key: 'signature_authorization',
            filesKey: 'signature_authorization_files',
            labelKey: 'tenant.companies.signatureAuthorizationFiles',
        },
        {
            key: 'additional_documents',
            filesKey: 'additional_documents_files',
            labelKey: 'tenant.companies.additionalDocumentsFiles',
        },
    ];

    const nameEnLabel = __('tenant.companies.nameEn');
    const slugLabel = __('tenant.companies.slug');
    const phoneLabel = __('tenant.companies.phone');
    const emailLabel = __('tenant.companies.email');
    const inspectionFeeLabel = __('tenant.companies.defaultInspectionFee');
    const shortDescArLabel = __('tenant.companies.shortDescriptionAr');
    const shortDescEnLabel = __('tenant.companies.shortDescriptionEn');
    const descArLabel = __('tenant.companies.descriptionAr');
    const descEnLabel = __('tenant.companies.descriptionEn');
    const contactNameLabel = __('tenant.companies.contactName');
    const contactPhoneLabel = __('tenant.companies.contactPhone');
    const contactPositionLabel = __('tenant.companies.contactPosition');
    const addressLabel = __('tenant.companies.address');
    const workingHoursLabel = __('tenant.companies.workingHours');
    const ibanLabel = __('tenant.companies.iban');
    const bankNameLabel = __('tenant.companies.bankName');
    const ownerNameArLabel = __('tenant.companies.primaryUser.ownerNameAr');
    const ownerNameEnLabel = __('tenant.companies.primaryUser.ownerNameEn');
    const ownerEmailLabel = __('tenant.companies.primaryUser.ownerEmail');
    const ownerPhoneLabel = __('tenant.companies.primaryUser.ownerPhone');
    const placeholderAlignStyle = { textAlign: 'right' as const };

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
                            <input
                                id="name_en"
                                name="name_en"
                                className={`form-control ${isInvalid('name_en') ? 'is-invalid' : ''}`}
                                value={data.name_en}
                                onChange={(e) => setData('name_en', e.target.value)}
                                placeholder={nameEnLabel}
                                aria-label={nameEnLabel}
                                dir="ltr"
                                style={placeholderAlignStyle}
                            />
                            <FieldError error={errors.name_en} />
                        </div>

                        <div className="form-group">
                            <input
                                id="slug"
                                name="slug"
                                className={`form-control ${isInvalid('slug') ? 'is-invalid' : ''}`}
                                value={data.slug}
                                onChange={(e) => setData('slug', e.target.value)}
                                placeholder={slugLabel}
                                aria-label={slugLabel}
                                dir="ltr"
                                style={placeholderAlignStyle}
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
                                    <input
                                        id="phone"
                                        name="phone"
                                        className={`form-control ${isInvalid('phone') ? 'is-invalid' : ''}`}
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        placeholder={phoneLabel}
                                        aria-label={phoneLabel}
                                        dir="ltr"
                                        style={placeholderAlignStyle}
                                    />
                                    <FieldError error={errors.phone} />
                                </div>
                            </div>
                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <input
                                        id="email"
                                        name="email"
                                        className={`form-control ${isInvalid('email') ? 'is-invalid' : ''}`}
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder={emailLabel}
                                        aria-label={emailLabel}
                                        dir="ltr"
                                        style={placeholderAlignStyle}
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
                                    <input
                                        id="default_inspection_fee"
                                        name="default_inspection_fee"
                                        className={`form-control ${isInvalid('default_inspection_fee') ? 'is-invalid' : ''}`}
                                        value={data.default_inspection_fee}
                                        onChange={(e) => setData('default_inspection_fee', e.target.value)}
                                        placeholder={inspectionFeeLabel}
                                        aria-label={inspectionFeeLabel}
                                        dir="ltr"
                                        style={placeholderAlignStyle}
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
                            <textarea
                                id="short_description_ar"
                                name="short_description_ar"
                                className={`form-control ${isInvalid('short_description_ar') ? 'is-invalid' : ''}`}
                                value={data.short_description_ar}
                                onChange={(e) => setData('short_description_ar', e.target.value)}
                                placeholder={shortDescArLabel}
                                aria-label={shortDescArLabel}
                                dir="rtl"
                                style={placeholderAlignStyle}
                                rows={2}
                            />
                            <FieldError error={errors.short_description_ar} />
                        </div>
                        <div className="form-group">
                            <textarea
                                id="description_ar"
                                name="description_ar"
                                className={`form-control ${isInvalid('description_ar') ? 'is-invalid' : ''}`}
                                value={data.description_ar}
                                onChange={(e) => setData('description_ar', e.target.value)}
                                placeholder={descArLabel}
                                aria-label={descArLabel}
                                dir="rtl"
                                style={placeholderAlignStyle}
                                rows={4}
                            />
                            <FieldError error={errors.description_ar} />
                        </div>
                    </div>
                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <textarea
                                id="short_description_en"
                                name="short_description_en"
                                className={`form-control ${isInvalid('short_description_en') ? 'is-invalid' : ''}`}
                                value={data.short_description_en}
                                onChange={(e) => setData('short_description_en', e.target.value)}
                                placeholder={shortDescEnLabel}
                                aria-label={shortDescEnLabel}
                                dir="ltr"
                                style={placeholderAlignStyle}
                                rows={2}
                            />
                            <FieldError error={errors.short_description_en} />
                        </div>
                        <div className="form-group">
                            <textarea
                                id="description_en"
                                name="description_en"
                                className={`form-control ${isInvalid('description_en') ? 'is-invalid' : ''}`}
                                value={data.description_en}
                                onChange={(e) => setData('description_en', e.target.value)}
                                placeholder={descEnLabel}
                                aria-label={descEnLabel}
                                dir="ltr"
                                style={placeholderAlignStyle}
                                rows={4}
                            />
                            <FieldError error={errors.description_en} />
                        </div>
                    </div>
                </div>

                <hr className="my-4" />

                <h5 className="mb-3">{__('tenant.companies.contactsSection')}</h5>
                {contacts.map((contact, index) => (
                    <div key={`contact-${index}`} className="border rounded p-3 mb-3">
                        <div className="d-flex justify-content-end mb-2">
                            <button
                                type="button"
                                className="btn btn-outline-danger btn-sm"
                                onClick={() => removeContact(index)}
                            >
                                {__('tenant.companies.removeContact')}
                            </button>
                        </div>
                        <div className="row">
                            <div className="col-12 col-md-4">
                                <div className="form-group mb-md-0">
                                    <input
                                        id={`contacts_${index}_name`}
                                        name={`contacts[${index}][name]`}
                                        className={`form-control ${anyErrors[`contacts.${index}.name`] ? 'is-invalid' : ''}`}
                                        value={contact.name}
                                        onChange={(e) => updateContact(index, 'name', e.target.value)}
                                        placeholder={contactNameLabel}
                                        aria-label={contactNameLabel}
                                        style={placeholderAlignStyle}
                                    />
                                    <FieldError error={anyErrors[`contacts.${index}.name`]} />
                                </div>
                            </div>
                            <div className="col-12 col-md-4">
                                <div className="form-group mb-md-0">
                                    <input
                                        id={`contacts_${index}_phone`}
                                        name={`contacts[${index}][phone]`}
                                        className={`form-control ${anyErrors[`contacts.${index}.phone`] ? 'is-invalid' : ''}`}
                                        value={contact.phone}
                                        onChange={(e) => updateContact(index, 'phone', e.target.value)}
                                        placeholder={contactPhoneLabel}
                                        aria-label={contactPhoneLabel}
                                        dir="ltr"
                                        style={placeholderAlignStyle}
                                    />
                                    <FieldError error={anyErrors[`contacts.${index}.phone`]} />
                                </div>
                            </div>
                            <div className="col-12 col-md-4">
                                <div className="form-group mb-0">
                                    <input
                                        id={`contacts_${index}_position`}
                                        name={`contacts[${index}][position]`}
                                        className={`form-control ${anyErrors[`contacts.${index}.position`] ? 'is-invalid' : ''}`}
                                        value={contact.position}
                                        onChange={(e) => updateContact(index, 'position', e.target.value)}
                                        placeholder={contactPositionLabel}
                                        aria-label={contactPositionLabel}
                                        style={placeholderAlignStyle}
                                    />
                                    <FieldError error={anyErrors[`contacts.${index}.position`]} />
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
                <div className="mb-4">
                    <button type="button" className="btn btn-outline-primary btn-sm" onClick={addContact}>
                        <i className="fas fa-plus mr-1" />
                        {__('tenant.companies.addContact')}
                    </button>
                    <FieldError error={anyErrors.contacts} />
                </div>

                <hr className="my-4" />

                <h5 className="mb-3">{__('tenant.companies.addressWorkingHoursSection')}</h5>
                <div className="row">
                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <textarea
                                id="address"
                                name="address"
                                className={`form-control ${isInvalid('address') ? 'is-invalid' : ''}`}
                                value={data.address ?? ''}
                                onChange={(e) => setData('address', e.target.value)}
                                placeholder={addressLabel}
                                aria-label={addressLabel}
                                style={placeholderAlignStyle}
                                rows={3}
                            />
                            <FieldError error={anyErrors.address} />
                        </div>
                    </div>
                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <textarea
                                id="working_hours"
                                name="working_hours"
                                className={`form-control ${isInvalid('working_hours') ? 'is-invalid' : ''}`}
                                value={data.working_hours ?? ''}
                                onChange={(e) => setData('working_hours', e.target.value)}
                                placeholder={workingHoursLabel}
                                aria-label={workingHoursLabel}
                                style={placeholderAlignStyle}
                                rows={3}
                            />
                            <FieldError error={anyErrors.working_hours} />
                        </div>
                    </div>
                </div>

                <hr className="my-4" />

                <h5 className="mb-2">{__('tenant.companies.documentsSection')}</h5>
                <p className="text-muted small mb-3">{__('tenant.companies.documentsHint')}</p>
                <div className="row">
                    {documentSections.map((section) => (
                        <div key={section.key} className="col-12 col-lg-4">
                            <div className="form-group">
                                <label htmlFor={section.filesKey}>{__(section.labelKey)}</label>
                                {mode === 'edit'
                                    ? visibleDocuments(documents?.[section.key]).map((doc) => (
                                          <div
                                              key={doc.id}
                                              className="d-flex align-items-center justify-content-between border rounded px-2 py-1 mb-2"
                                          >
                                              <a href={doc.url} target="_blank" rel="noreferrer" className="small text-truncate mr-2">
                                                  {doc.file_name || doc.url}
                                              </a>
                                              <button
                                                  type="button"
                                                  className="btn btn-outline-danger btn-sm"
                                                  onClick={() => markDocumentForRemoval(doc.id)}
                                              >
                                                  {__('tenant.companies.removeDocument')}
                                              </button>
                                          </div>
                                      ))
                                    : null}
                                <input
                                    id={section.filesKey}
                                    name={`${section.filesKey}[]`}
                                    type="file"
                                    multiple
                                    accept="image/*,application/pdf,.pdf"
                                    className={`form-control ${anyErrors[section.filesKey] ? 'is-invalid' : ''}`}
                                    onChange={(e) =>
                                        setData(section.filesKey, Array.from(e.target.files ?? []) as any)
                                    }
                                />
                                <FieldError error={anyErrors[section.filesKey]} />
                                <FieldError error={anyErrors[`${section.filesKey}.0`]} />
                            </div>
                        </div>
                    ))}
                </div>
                <FieldError error={anyErrors.remove_document_ids} />

                <hr className="my-4" />

                <h5 className="mb-3">{__('tenant.companies.bankingSection')}</h5>
                <div className="row">
                    <div className="col-12 col-md-6">
                        <div className="form-group">
                            <input
                                id="iban"
                                name="iban"
                                className={`form-control ${isInvalid('iban') ? 'is-invalid' : ''}`}
                                value={data.iban ?? ''}
                                onChange={(e) => setData('iban', e.target.value)}
                                placeholder={ibanLabel}
                                aria-label={ibanLabel}
                                dir="ltr"
                                style={placeholderAlignStyle}
                            />
                            <FieldError error={anyErrors.iban} />
                        </div>
                    </div>
                    <div className="col-12 col-md-6">
                        <div className="form-group">
                            <input
                                id="bank_name"
                                name="bank_name"
                                className={`form-control ${isInvalid('bank_name') ? 'is-invalid' : ''}`}
                                value={data.bank_name ?? ''}
                                onChange={(e) => setData('bank_name', e.target.value)}
                                placeholder={bankNameLabel}
                                aria-label={bankNameLabel}
                                style={placeholderAlignStyle}
                            />
                            <FieldError error={anyErrors.bank_name} />
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
                                            {ownerFieldsRequired ? (
                                                <label htmlFor="owner_name_ar">
                                                    {ownerNameArLabel}
                                                    <RequiredMark />
                                                </label>
                                            ) : null}
                                            <input
                                                id="owner_name_ar"
                                                name="owner_name_ar"
                                                className={`form-control ${requiredClient.owner_name_ar || anyErrors.owner_name_ar ? 'is-invalid' : ''}`}
                                                value={data.owner_name_ar ?? ''}
                                                onChange={(e) => setData('owner_name_ar', e.target.value)}
                                                placeholder={ownerFieldsRequired ? undefined : ownerNameArLabel}
                                                aria-label={ownerNameArLabel}
                                                dir="rtl"
                                                style={ownerFieldsRequired ? undefined : placeholderAlignStyle}
                                            />
                                            <FieldError error={anyErrors.owner_name_ar} />
                                        </div>

                                        <div className="form-group">
                                            {ownerFieldsRequired ? (
                                                <label htmlFor="owner_email">
                                                    {ownerEmailLabel}
                                                    <RequiredMark />
                                                </label>
                                            ) : null}
                                            <input
                                                id="owner_email"
                                                name="owner_email"
                                                className={`form-control ${requiredClient.owner_email || anyErrors.owner_email ? 'is-invalid' : ''}`}
                                                value={data.owner_email ?? ''}
                                                onChange={(e) => setData('owner_email', e.target.value)}
                                                placeholder={ownerFieldsRequired ? undefined : ownerEmailLabel}
                                                aria-label={ownerEmailLabel}
                                                dir="ltr"
                                                style={ownerFieldsRequired ? undefined : placeholderAlignStyle}
                                            />
                                            <FieldError error={anyErrors.owner_email} />
                                        </div>

                                        <div className="form-group">
                                            <input
                                                id="owner_phone"
                                                name="owner_phone"
                                                className={`form-control ${requiredClient.owner_phone || anyErrors.owner_phone ? 'is-invalid' : ''}`}
                                                value={data.owner_phone ?? ''}
                                                onChange={(e) => setData('owner_phone', e.target.value)}
                                                placeholder={ownerPhoneLabel}
                                                aria-label={ownerPhoneLabel}
                                                dir="ltr"
                                                style={placeholderAlignStyle}
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
                                            <input
                                                id="owner_name_en"
                                                name="owner_name_en"
                                                className={`form-control ${requiredClient.owner_name_en || anyErrors.owner_name_en ? 'is-invalid' : ''}`}
                                                value={data.owner_name_en ?? ''}
                                                onChange={(e) => setData('owner_name_en', e.target.value)}
                                                placeholder={ownerNameEnLabel}
                                                aria-label={ownerNameEnLabel}
                                                dir="ltr"
                                                style={placeholderAlignStyle}
                                            />
                                            <FieldError error={anyErrors.owner_name_en} />
                                        </div>

                                        <div className="form-group">
                                            <label htmlFor="owner_password">
                                                {__('tenant.companies.primaryUser.ownerPassword')}
                                                {passwordRequired ? <RequiredMark /> : null}
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
                                                required={passwordRequired}
                                            />
                                            <FieldError error={anyErrors.owner_password} />
                                        </div>

                                        <div className="form-group">
                                            <label htmlFor="owner_password_confirmation">
                                                {__('tenant.companies.primaryUser.ownerPasswordConfirmation')}
                                                {passwordRequired ? <RequiredMark /> : null}
                                            </label>
                                            <PasswordInput
                                                id="owner_password_confirmation"
                                                name="owner_password_confirmation"
                                                value={data.owner_password_confirmation ?? ''}
                                                onChange={(v) => setData('owner_password_confirmation', v)}
                                                isInvalid={Boolean(requiredClient.owner_password_confirmation || anyErrors.owner_password_confirmation)}
                                                autoComplete="new-password"
                                                required={passwordRequired}
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
