import TableCard from '@/Components/ui/TableCard';
import StatusBadge from '@/Components/ui/StatusBadge';
import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';

function safeObjectUrl(file: File | null): string | null {
    try {
        return file ? URL.createObjectURL(file) : null;
    } catch {
        return null;
    }
}

type ServiceCategoryOption = { id: number; name_ar: string; name_en: string | null };

type TechnicianPayload = {
    id: number;
    first_name_ar: string | null;
    second_name_ar: string | null;
    family_name_ar: string | null;
    first_name_en: string | null;
    second_name_en: string | null;
    family_name_en: string | null;
    civil_id_number: string | null;
    civil_id_document_url: string | null;
    civil_id_document_name: string | null;
    employee_code: string | null;
    display_name_ar: string | null;
    display_name_en: string | null;
    bio_ar: string | null;
    bio_en: string | null;
    job_title_ar: string | null;
    job_title_en: string | null;
    is_active: boolean;
    availability_status?: string | null;
    availability_status_label?: string | null;
    review_status?: string | null;
    rejection_reason?: string | null;
    pending_changes?: Record<string, unknown> | null;
    created_at?: string | null;
    company?: { id: number; name: string | null; slug: string | null } | null;
    user: { name: string; name_ar?: string | null; name_en?: string | null; email: string | null; phone: string | null; avatar_url?: string | null };
    service_category_ids: number[];
};

export default function CompanyTechniciansEdit({
    technician,
    serviceCategories,
}: {
    technician: TechnicianPayload;
    serviceCategories: ServiceCategoryOption[];
}) {
    const { direction, enumLabels } = usePage<PageProps>().props;
    const isRtl = direction === 'rtl';
    const align = isRtl ? 'right' : 'left';
    const statusLabels = enumLabels?.technician_review_status ?? {};
    const reviewStatus = String(technician.review_status ?? '').toLowerCase();
    const isPendingDeletion = reviewStatus === 'pending_deletion';
    const hasCivilIdDocument = Boolean(technician.civil_id_document_url);

    const { data, setData, post, processing, errors, transform } = useForm({
        first_name_ar: technician.first_name_ar ?? '',
        second_name_ar: technician.second_name_ar ?? '',
        family_name_ar: technician.family_name_ar ?? '',
        first_name_en: technician.first_name_en ?? '',
        second_name_en: technician.second_name_en ?? '',
        family_name_en: technician.family_name_en ?? '',
        civil_id_number: technician.civil_id_number ?? '',
        civil_id_document: null as File | null,
        avatar: null as File | null,
        email: technician.user.email ?? '',
        phone: technician.user.phone ?? '',
        employee_code: technician.employee_code ?? '',
        display_name_ar: technician.display_name_ar ?? '',
        display_name_en: technician.display_name_en ?? '',
        bio_ar: technician.bio_ar ?? '',
        bio_en: technician.bio_en ?? '',
        job_title_ar: technician.job_title_ar ?? '',
        job_title_en: technician.job_title_en ?? '',
        service_category_ids: technician.service_category_ids,
    });

    const resetForm = useForm({
        password: '',
        password_confirmation: '',
    });

    const [attemptedSubmit, setAttemptedSubmit] = useState(false);
    const [showResetPassword, setShowResetPassword] = useState(false);
    const [showResetPasswordConfirmation, setShowResetPasswordConfirmation] = useState(false);

    const refs = {
        first_name_ar: useRef<HTMLInputElement | null>(null),
        second_name_ar: useRef<HTMLInputElement | null>(null),
        family_name_ar: useRef<HTMLInputElement | null>(null),
        civil_id_number: useRef<HTMLInputElement | null>(null),
        email: useRef<HTMLInputElement | null>(null),
        phone: useRef<HTMLInputElement | null>(null),
    };

    const focusField = (name: keyof typeof refs) => {
        const el = refs[name].current;
        if (!el) return;
        el.scrollIntoView({ block: 'center' });
        el.focus();
    };

    const focusFirstError = (errs: Record<string, string>) => {
        const order: (keyof typeof refs)[] = ['first_name_ar', 'second_name_ar', 'family_name_ar', 'civil_id_number', 'email', 'phone'];
        for (const key of order) {
            if (errs[key]) {
                focusField(key);
                return;
            }
        }
        if (errs.civil_id_document) {
            const el = document.getElementById('civil_id_document') as HTMLInputElement | null;
            if (el) {
                el.scrollIntoView({ block: 'center' });
                el.focus();
            }
        }
    };

    const localErrors = useMemo(() => {
        if (!attemptedSubmit) return {};
        const invalid: Record<string, string> = {};
        const phone = String(data.phone ?? '').trim();
        const civilId = String(data.civil_id_number ?? '').trim();

        if (!String(data.first_name_ar ?? '').trim()) invalid.first_name_ar = __('tenant.technicians.validation_first_name_ar_required');
        if (!String(data.second_name_ar ?? '').trim()) invalid.second_name_ar = __('tenant.technicians.validation_second_name_ar_required');
        if (!String(data.family_name_ar ?? '').trim()) invalid.family_name_ar = __('tenant.technicians.validation_family_name_ar_required');
        if (!civilId) invalid.civil_id_number = __('tenant.technicians.validation_civil_id_number_required');
        else if (!/^\d{12}$/.test(civilId)) invalid.civil_id_number = __('tenant.technicians.validation_civil_id_number_digits');
        if (!hasCivilIdDocument && !data.civil_id_document) {
            invalid.civil_id_document = __('tenant.technicians.validation_civil_id_document_required');
        }
        if (!phone) invalid.phone = __('tenant.technicians.validation_phone_required');
        else if (!/^\d{8}$/.test(phone)) invalid.phone = __('tenant.technicians.phone_must_be_8_digits');

        return invalid;
    }, [attemptedSubmit, data.first_name_ar, data.second_name_ar, data.family_name_ar, data.civil_id_number, data.civil_id_document, data.phone, hasCivilIdDocument]);

    const avatarPreviewUrl = useMemo(() => safeObjectUrl(data.avatar), [data.avatar]);
    useEffect(() => {
        return () => {
            if (avatarPreviewUrl) URL.revokeObjectURL(avatarPreviewUrl);
        };
    }, [avatarPreviewUrl]);

    const [servicesOpen, setServicesOpen] = useState(false);
    const [servicesQuery, setServicesQuery] = useState('');
    const servicesMenuRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        if (!servicesOpen) return;
        const onKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'Escape') setServicesOpen(false);
        };
        const onMouseDown = (e: MouseEvent) => {
            const el = servicesMenuRef.current;
            if (!el) return;
            if (el.contains(e.target as Node)) return;
            setServicesOpen(false);
        };
        document.addEventListener('keydown', onKeyDown);
        document.addEventListener('mousedown', onMouseDown);
        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.removeEventListener('mousedown', onMouseDown);
        };
    }, [servicesOpen]);

    const selectedServiceIds = data.service_category_ids ?? [];
    const selectedServices = useMemo(() => {
        const map = new Map(serviceCategories.map((c) => [c.id, c] as const));
        return selectedServiceIds.map((id) => map.get(id)).filter(Boolean) as ServiceCategoryOption[];
    }, [selectedServiceIds, serviceCategories]);

    const composedAr = [data.first_name_ar, data.second_name_ar, data.family_name_ar].filter(Boolean).join(' ');
    const currentDisplayName = data.display_name_ar || data.display_name_en || composedAr || technician.user.name;
    const currentJobTitle = data.job_title_ar || data.job_title_en || '—';
    const currentAvatarUrl = avatarPreviewUrl ?? technician.user.avatar_url ?? null;

    const filteredServices = useMemo(() => {
        const q = servicesQuery.trim().toLowerCase();
        if (!q) return serviceCategories;
        return serviceCategories.filter((c) => `${c.name_ar ?? ''} ${c.name_en ?? ''}`.toLowerCase().includes(q));
    }, [servicesQuery, serviceCategories]);

    const toggleService = (id: number) => {
        const exists = selectedServiceIds.includes(id);
        const next = exists ? selectedServiceIds.filter((x) => x !== id) : [...selectedServiceIds, id];
        setData('service_category_ids', next);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isPendingDeletion) return;
        setAttemptedSubmit(true);

        const nextLocal: Record<string, string> = {};
        const phone = String(data.phone ?? '').trim();
        const civilId = String(data.civil_id_number ?? '').trim();
        if (!String(data.first_name_ar ?? '').trim()) nextLocal.first_name_ar = __('tenant.technicians.validation_first_name_ar_required');
        if (!String(data.second_name_ar ?? '').trim()) nextLocal.second_name_ar = __('tenant.technicians.validation_second_name_ar_required');
        if (!String(data.family_name_ar ?? '').trim()) nextLocal.family_name_ar = __('tenant.technicians.validation_family_name_ar_required');
        if (!civilId) nextLocal.civil_id_number = __('tenant.technicians.validation_civil_id_number_required');
        else if (!/^\d{12}$/.test(civilId)) nextLocal.civil_id_number = __('tenant.technicians.validation_civil_id_number_digits');
        if (!hasCivilIdDocument && !data.civil_id_document) nextLocal.civil_id_document = __('tenant.technicians.validation_civil_id_document_required');
        if (!phone) nextLocal.phone = __('tenant.technicians.validation_phone_required');
        else if (!/^\d{8}$/.test(phone)) nextLocal.phone = __('tenant.technicians.phone_must_be_8_digits');

        if (Object.keys(nextLocal).length > 0) {
            focusFirstError(nextLocal);
            return;
        }

        transform((payload) => ({
            ...payload,
            _method: 'patch',
        }));
        post(route('company.technicians.update', technician.id), {
            forceFormData: true,
            onError: (errs) => focusFirstError(errs as any),
        });
    };

    const submitReset = (e: React.FormEvent) => {
        e.preventDefault();
        resetForm.post(route('company.technicians.reset-password', technician.id));
    };

    const reviewBadge = () => {
        if (reviewStatus === 'approved') return <StatusBadge variant="success">{statusLabels.approved ?? reviewStatus}</StatusBadge>;
        if (reviewStatus === 'pending') return <StatusBadge variant="warning">{statusLabels.pending ?? reviewStatus}</StatusBadge>;
        if (reviewStatus === 'pending_update') return <StatusBadge variant="warning">{statusLabels.pending_update ?? reviewStatus}</StatusBadge>;
        if (reviewStatus === 'pending_deletion') return <StatusBadge variant="danger">{statusLabels.pending_deletion ?? reviewStatus}</StatusBadge>;
        if (reviewStatus === 'rejected') return <StatusBadge variant="danger">{statusLabels.rejected ?? reviewStatus}</StatusBadge>;
        return <span className="text-muted">—</span>;
    };

    return (
        <CompanyDashboardLayout header={<h4 className="m-0 font-weight-bold text-dark">{__('tenant.technicians.editTitle')}</h4>}>
            <Head title={__('tenant.technicians.editTitle')} />

            <div className="mb-3">
                <Link href={route('company.technicians.index')} className="btn btn-outline-secondary btn-sm">
                    <i className={isRtl ? 'fas fa-arrow-right ms-1' : 'fas fa-arrow-left me-1'} />
                    {__('tenant.technicians.back')}
                </Link>
            </div>

            {reviewStatus === 'pending' ? <div className="callout callout-warning">{__('tenant.technicians.pending_review_notice')}</div> : null}
            {reviewStatus === 'pending_update' ? <div className="callout callout-warning">{__('tenant.technicians.pending_update_notice')}</div> : null}
            {reviewStatus === 'pending_deletion' ? <div className="callout callout-danger">{__('tenant.technicians.pending_deletion_notice')}</div> : null}
            {reviewStatus === 'approved' ? <div className="callout callout-success">{__('tenant.technicians.approved_notice')}</div> : null}
            {reviewStatus === 'rejected' ? (
                <div className="callout callout-danger">
                    <div className="font-weight-bold">{__('tenant.technicians.rejected_notice')}</div>
                    {technician.rejection_reason ? (
                        <div className="mt-1">
                            {__('dashboard.technician_reviews.rejection_reason')}: {technician.rejection_reason}
                        </div>
                    ) : null}
                </div>
            ) : null}

            <TableCard title={__('tenant.technicians.profileSection')} bodyPadding>
                <div className="row align-items-center">
                    <div className="col-lg-4 mb-3 mb-lg-0">
                        <div className="d-flex align-items-center">
                            {currentAvatarUrl ? (
                                <img
                                    src={currentAvatarUrl}
                                    alt={currentDisplayName}
                                    className="rounded border bg-white"
                                    style={{ width: 84, height: 84, objectFit: 'cover' }}
                                />
                            ) : (
                                <div
                                    className="rounded border bg-light d-flex align-items-center justify-content-center text-muted"
                                    style={{ width: 84, height: 84 }}
                                    aria-label={__('tenant.technicians.avatar')}
                                >
                                    <i className="fas fa-user" />
                                </div>
                            )}
                            <div className="mx-3">
                                <div className="h5 mb-1">{currentDisplayName}</div>
                                <div className="text-muted small">{currentJobTitle}</div>
                                <div className="text-muted small" dir="ltr">
                                    {data.email || '—'} {data.phone ? ` • ${data.phone}` : ''}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="col-lg-8">
                        <div className="row">
                            <div className="col-md-4 mb-3">
                                <div className="text-muted small">{__('tenant.technicians.review_status')}</div>
                                <div>{reviewBadge()}</div>
                            </div>
                            <div className="col-md-4 mb-3">
                                <div className="text-muted small">{__('tenant.technicians.active')}</div>
                                <div>
                                    {technician.is_active ? (
                                        <StatusBadge variant="success">{__('tenant.yes')}</StatusBadge>
                                    ) : (
                                        <StatusBadge variant="secondary">{__('tenant.no')}</StatusBadge>
                                    )}
                                </div>
                            </div>
                            <div className="col-md-4 mb-3">
                                <div className="text-muted small">{__('tenant.technicians.availability')}</div>
                                <div>{technician.availability_status_label ?? technician.availability_status ?? '—'}</div>
                            </div>
                            <div className="col-md-4 mb-3">
                                <div className="text-muted small">{__('tenant.technicians.companySection')}</div>
                                <div>{technician.company?.name ?? '—'}</div>
                            </div>
                            <div className="col-md-4 mb-3">
                                <div className="text-muted small">{__('tenant.technicians.employeeCode')}</div>
                                <div dir="ltr">{data.employee_code || '—'}</div>
                            </div>
                            <div className="col-md-4 mb-3">
                                <div className="text-muted small">{__('tenant.technicians.civilIdNumber')}</div>
                                <div dir="ltr">{data.civil_id_number || '—'}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </TableCard>

            <TableCard title={__('tenant.technicians.editTitle')} bodyPadding>
                <form onSubmit={submit}>
                    <fieldset disabled={isPendingDeletion}>
                        <div className="row">
                            {(Object.keys(localErrors).length > 0 || Object.keys(errors).length > 0) && attemptedSubmit ? (
                                <div className="col-12">
                                    <div className="alert alert-danger" role="alert">
                                        <div className="font-weight-bold mb-1">{__('tenant.technicians.validation_summary_title')}</div>
                                        <ul className="mb-0 pl-3">
                                            {Array.from(new Set([...Object.values(localErrors), ...Object.values(errors).filter((m): m is string => typeof m === 'string')])).map((message) => (
                                                <li key={message}>{message}</li>
                                            ))}
                                        </ul>
                                    </div>
                                </div>
                            ) : null}

                            <div className="col-md-4">
                                <div className="form-group">
                                    <label>
                                        {__('tenant.technicians.firstNameAr')}
                                        <span className="text-danger"> *</span>
                                    </label>
                                    <input
                                        ref={refs.first_name_ar}
                                        className={`form-control ${(errors.first_name_ar || localErrors.first_name_ar) ? 'is-invalid' : ''}`}
                                        dir="rtl"
                                        value={data.first_name_ar}
                                        onChange={(e) => setData('first_name_ar', e.target.value)}
                                        style={{ textAlign: 'right' }}
                                    />
                                    {errors.first_name_ar || localErrors.first_name_ar ? <div className="text-danger small mt-1">{errors.first_name_ar ?? localErrors.first_name_ar}</div> : null}
                                </div>
                            </div>
                            <div className="col-md-4">
                                <div className="form-group">
                                    <label>
                                        {__('tenant.technicians.secondNameAr')}
                                        <span className="text-danger"> *</span>
                                    </label>
                                    <input
                                        ref={refs.second_name_ar}
                                        className={`form-control ${(errors.second_name_ar || localErrors.second_name_ar) ? 'is-invalid' : ''}`}
                                        dir="rtl"
                                        value={data.second_name_ar}
                                        onChange={(e) => setData('second_name_ar', e.target.value)}
                                        style={{ textAlign: 'right' }}
                                    />
                                    {errors.second_name_ar || localErrors.second_name_ar ? <div className="text-danger small mt-1">{errors.second_name_ar ?? localErrors.second_name_ar}</div> : null}
                                </div>
                            </div>
                            <div className="col-md-4">
                                <div className="form-group">
                                    <label>
                                        {__('tenant.technicians.familyNameAr')}
                                        <span className="text-danger"> *</span>
                                    </label>
                                    <input
                                        ref={refs.family_name_ar}
                                        className={`form-control ${(errors.family_name_ar || localErrors.family_name_ar) ? 'is-invalid' : ''}`}
                                        dir="rtl"
                                        value={data.family_name_ar}
                                        onChange={(e) => setData('family_name_ar', e.target.value)}
                                        style={{ textAlign: 'right' }}
                                    />
                                    {errors.family_name_ar || localErrors.family_name_ar ? <div className="text-danger small mt-1">{errors.family_name_ar ?? localErrors.family_name_ar}</div> : null}
                                </div>
                            </div>

                            <div className="col-md-4">
                                <div className="form-group">
                                    <input
                                        className="form-control"
                                        placeholder={__('tenant.technicians.firstNameEn')}
                                        aria-label={__('tenant.technicians.firstNameEn')}
                                        dir="ltr"
                                        value={data.first_name_en}
                                        onChange={(e) => setData('first_name_en', e.target.value)}
                                        style={{ textAlign: align }}
                                    />
                                </div>
                            </div>
                            <div className="col-md-4">
                                <div className="form-group">
                                    <input
                                        className="form-control"
                                        placeholder={__('tenant.technicians.secondNameEn')}
                                        aria-label={__('tenant.technicians.secondNameEn')}
                                        dir="ltr"
                                        value={data.second_name_en}
                                        onChange={(e) => setData('second_name_en', e.target.value)}
                                        style={{ textAlign: align }}
                                    />
                                </div>
                            </div>
                            <div className="col-md-4">
                                <div className="form-group">
                                    <input
                                        className="form-control"
                                        placeholder={__('tenant.technicians.familyNameEn')}
                                        aria-label={__('tenant.technicians.familyNameEn')}
                                        dir="ltr"
                                        value={data.family_name_en}
                                        onChange={(e) => setData('family_name_en', e.target.value)}
                                        style={{ textAlign: align }}
                                    />
                                </div>
                            </div>

                            <div className="col-md-6">
                                <div className="form-group">
                                    <label>
                                        {__('tenant.technicians.civilIdNumber')}
                                        <span className="text-danger"> *</span>
                                    </label>
                                    <input
                                        ref={refs.civil_id_number}
                                        className={`form-control ${(errors.civil_id_number || localErrors.civil_id_number) ? 'is-invalid' : ''}`}
                                        dir="ltr"
                                        value={data.civil_id_number}
                                        onChange={(e) => setData('civil_id_number', e.target.value)}
                                        style={{ textAlign: align }}
                                    />
                                    {errors.civil_id_number || localErrors.civil_id_number ? <div className="text-danger small mt-1">{errors.civil_id_number ?? localErrors.civil_id_number}</div> : null}
                                </div>
                            </div>
                            <div className="col-md-6">
                                <div className="form-group">
                                    <label>
                                        {__('tenant.technicians.civilIdDocument')}
                                        {!hasCivilIdDocument ? <span className="text-danger"> *</span> : null}
                                    </label>
                                    {technician.civil_id_document_url ? (
                                        <div className="mb-2">
                                            <a href={technician.civil_id_document_url} target="_blank" rel="noreferrer">
                                                {technician.civil_id_document_name ?? __('tenant.technicians.civilIdDocumentCurrent')}
                                            </a>
                                        </div>
                                    ) : null}
                                    <div className="custom-file">
                                        <input
                                            type="file"
                                            accept="image/*,.pdf,application/pdf"
                                            className={`custom-file-input ${(errors.civil_id_document || localErrors.civil_id_document) ? 'is-invalid' : ''}`}
                                            id="civil_id_document"
                                            onChange={(e) => setData('civil_id_document', e.target.files?.[0] ?? null)}
                                        />
                                        <label className="custom-file-label" htmlFor="civil_id_document">
                                            {data.civil_id_document ? data.civil_id_document.name : __('tenant.choose_file')}
                                        </label>
                                    </div>
                                    {errors.civil_id_document || localErrors.civil_id_document ? <div className="text-danger small mt-1">{errors.civil_id_document ?? localErrors.civil_id_document}</div> : null}
                                </div>
                            </div>

                            <div className="col-12">
                                <div className="form-group">
                                    <label>{__('tenant.technicians.avatar')}</label>
                                    <div className="custom-file">
                                        <input
                                            type="file"
                                            accept="image/*"
                                            className={`custom-file-input ${errors.avatar ? 'is-invalid' : ''}`}
                                            id="avatar"
                                            onChange={(e) => setData('avatar', e.target.files?.[0] ?? null)}
                                        />
                                        <label className="custom-file-label" htmlFor="avatar">
                                            {data.avatar ? data.avatar.name : __('tenant.choose_file')}
                                        </label>
                                    </div>
                                    {errors.avatar ? <div className="text-danger small mt-1">{errors.avatar}</div> : null}
                                </div>
                            </div>

                            <div className="col-md-6">
                                <div className="form-group">
                                    <input
                                        ref={refs.email}
                                        className={`form-control ${errors.email ? 'is-invalid' : ''}`}
                                        type="email"
                                        placeholder={__('tenant.technicians.email')}
                                        aria-label={__('tenant.technicians.email')}
                                        dir="ltr"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        style={{ textAlign: align }}
                                    />
                                    {errors.email ? <div className="text-danger small mt-1">{errors.email}</div> : null}
                                </div>
                            </div>
                            <div className="col-md-6">
                                <div className="form-group">
                                    <label>
                                        {__('tenant.technicians.phone')}
                                        <span className="text-danger"> *</span>
                                    </label>
                                    <input
                                        ref={refs.phone}
                                        className={`form-control ${(errors.phone || localErrors.phone) ? 'is-invalid' : ''}`}
                                        dir="ltr"
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        style={{ textAlign: align }}
                                    />
                                    {errors.phone || localErrors.phone ? <div className="text-danger small mt-1">{errors.phone ?? localErrors.phone}</div> : null}
                                </div>
                            </div>

                            <div className="col-md-6">
                                <div className="form-group">
                                    <input
                                        className="form-control"
                                        placeholder={__('tenant.technicians.displayNameAr')}
                                        aria-label={__('tenant.technicians.displayNameAr')}
                                        dir="rtl"
                                        value={data.display_name_ar}
                                        onChange={(e) => setData('display_name_ar', e.target.value)}
                                        style={{ textAlign: align }}
                                    />
                                </div>
                            </div>
                            <div className="col-md-6">
                                <div className="form-group">
                                    <input
                                        className="form-control"
                                        placeholder={__('tenant.technicians.displayNameEn')}
                                        aria-label={__('tenant.technicians.displayNameEn')}
                                        dir="ltr"
                                        value={data.display_name_en}
                                        onChange={(e) => setData('display_name_en', e.target.value)}
                                        style={{ textAlign: align }}
                                    />
                                </div>
                            </div>

                            <div className="col-md-6">
                                <div className="form-group">
                                    <input
                                        className="form-control"
                                        placeholder={__('tenant.technicians.employeeCode')}
                                        aria-label={__('tenant.technicians.employeeCode')}
                                        dir="ltr"
                                        value={data.employee_code}
                                        onChange={(e) => setData('employee_code', e.target.value)}
                                        style={{ textAlign: align }}
                                    />
                                </div>
                            </div>
                            <div className="col-md-6" />

                            <div className="col-md-6">
                                <div className="form-group">
                                    <input
                                        className="form-control"
                                        placeholder={__('tenant.technicians.jobTitleAr')}
                                        aria-label={__('tenant.technicians.jobTitleAr')}
                                        dir="rtl"
                                        value={data.job_title_ar}
                                        onChange={(e) => setData('job_title_ar', e.target.value)}
                                        style={{ textAlign: align }}
                                    />
                                </div>
                            </div>
                            <div className="col-md-6">
                                <div className="form-group">
                                    <input
                                        className="form-control"
                                        placeholder={__('tenant.technicians.jobTitleEn')}
                                        aria-label={__('tenant.technicians.jobTitleEn')}
                                        dir="ltr"
                                        value={data.job_title_en}
                                        onChange={(e) => setData('job_title_en', e.target.value)}
                                        style={{ textAlign: align }}
                                    />
                                </div>
                            </div>

                            <div className="col-md-6">
                                <div className="form-group">
                                    <textarea
                                        className="form-control"
                                        rows={3}
                                        placeholder={__('tenant.technicians.bioAr')}
                                        aria-label={__('tenant.technicians.bioAr')}
                                        dir="rtl"
                                        value={data.bio_ar}
                                        onChange={(e) => setData('bio_ar', e.target.value)}
                                        style={{ textAlign: align }}
                                    />
                                </div>
                            </div>
                            <div className="col-md-6">
                                <div className="form-group">
                                    <textarea
                                        className="form-control"
                                        rows={3}
                                        placeholder={__('tenant.technicians.bioEn')}
                                        aria-label={__('tenant.technicians.bioEn')}
                                        dir="ltr"
                                        value={data.bio_en}
                                        onChange={(e) => setData('bio_en', e.target.value)}
                                        style={{ textAlign: align }}
                                    />
                                </div>
                            </div>

                            <div className="col-12">
                                <div className="form-group">
                                    <label>{__('tenant.technicians.categories')}</label>
                                    {selectedServices.length > 0 ? (
                                        <div className="mb-2">
                                            <div className="text-muted small mb-1">{__('tenant.technicians.selectedServices')}</div>
                                            <div className="d-flex flex-wrap" style={{ gap: 6 }}>
                                                {selectedServices.map((s) => (
                                                    <span key={s.id} className="badge badge-info">
                                                        {s.name_ar}
                                                        <button type="button" className="btn btn-link p-0 ml-2 text-white" style={{ textDecoration: 'none' }} onClick={() => toggleService(s.id)} aria-label="remove">
                                                            <i className="fas fa-times" />
                                                        </button>
                                                    </span>
                                                ))}
                                            </div>
                                        </div>
                                    ) : null}
                                    <div className="position-relative" ref={servicesMenuRef}>
                                        <button type="button" className="btn btn-outline-secondary w-100 d-flex justify-content-between align-items-center" onClick={() => setServicesOpen((v) => !v)}>
                                            <span className="text-truncate">
                                                {selectedServices.length ? selectedServices.map((s) => s.name_ar).join('، ') : __('tenant.technicians.servicesSelectPlaceholder')}
                                            </span>
                                            <i className="fas fa-chevron-down" />
                                        </button>
                                        {servicesOpen ? (
                                            <div className="border bg-white rounded shadow-sm p-2" style={{ position: 'absolute', top: '100%', right: 0, left: 0, zIndex: 2000, maxHeight: 320, overflowY: 'auto' }}>
                                                <input
                                                    className="form-control form-control-sm mb-2"
                                                    placeholder={__('tenant.search')}
                                                    aria-label={__('tenant.search')}
                                                    value={servicesQuery}
                                                    onChange={(e) => setServicesQuery(e.target.value)}
                                                    style={{ textAlign: align }}
                                                />
                                                {filteredServices.map((c) => {
                                                    const checked = selectedServiceIds.includes(c.id);
                                                    return (
                                                        <label key={c.id} className="d-flex align-items-center mb-1" style={{ gap: 8, cursor: 'pointer' }}>
                                                            <input type="checkbox" checked={checked} onChange={() => toggleService(c.id)} />
                                                            <span>
                                                                {c.name_ar}
                                                                {c.name_en ? <span className="text-muted"> — {c.name_en}</span> : null}
                                                            </span>
                                                        </label>
                                                    );
                                                })}
                                            </div>
                                        ) : null}
                                    </div>
                                </div>
                            </div>

                            <div className="col-12">
                                <button type="submit" className="btn btn-primary px-4" disabled={processing || isPendingDeletion}>
                                    <i className="fas fa-save mr-1" />
                                    {__('tenant.technicians.save')}
                                </button>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </TableCard>

            <TableCard title={__('tenant.technicians.resetPasswordTitle')} bodyPadding>
                <p className="text-muted mb-3">{__('tenant.technicians.resetPasswordHint')}</p>
                <form onSubmit={submitReset} className="row">
                    <div className="col-md-6">
                        <div className="form-group">
                            <label>{__('tenant.technicians.password')}</label>
                            <div className="input-group">
                                <input
                                    type={showResetPassword ? 'text' : 'password'}
                                    className={`form-control ${resetForm.errors.password ? 'is-invalid' : ''}`}
                                    value={resetForm.data.password}
                                    onChange={(e) => resetForm.setData('password', e.target.value)}
                                />
                                <div className="input-group-append">
                                    <button type="button" className="btn btn-outline-secondary" onClick={() => setShowResetPassword((v) => !v)} aria-label={__('tenant.technicians.togglePasswordVisibility')}>
                                        <i className={showResetPassword ? 'fas fa-eye-slash' : 'fas fa-eye'} />
                                    </button>
                                </div>
                            </div>
                            {resetForm.errors.password ? <div className="text-danger small mt-1">{resetForm.errors.password}</div> : null}
                        </div>
                    </div>
                    <div className="col-md-6">
                        <div className="form-group">
                            <label>{__('tenant.technicians.passwordConfirmation')}</label>
                            <div className="input-group">
                                <input
                                    type={showResetPasswordConfirmation ? 'text' : 'password'}
                                    className="form-control"
                                    value={resetForm.data.password_confirmation}
                                    onChange={(e) => resetForm.setData('password_confirmation', e.target.value)}
                                />
                                <div className="input-group-append">
                                    <button type="button" className="btn btn-outline-secondary" onClick={() => setShowResetPasswordConfirmation((v) => !v)} aria-label={__('tenant.technicians.togglePasswordVisibility')}>
                                        <i className={showResetPasswordConfirmation ? 'fas fa-eye-slash' : 'fas fa-eye'} />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className="col-12">
                        <button type="submit" className="btn btn-outline-primary px-4" disabled={resetForm.processing}>
                            <i className="fas fa-key mr-1" />
                            {__('tenant.technicians.resetPassword')}
                        </button>
                    </div>
                </form>
            </TableCard>
        </CompanyDashboardLayout>
    );
}
