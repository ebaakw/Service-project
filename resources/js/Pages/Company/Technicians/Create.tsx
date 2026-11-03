import TableCard from '@/Components/ui/TableCard';
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

export default function CompanyTechniciansCreate({
    serviceCategories,
}: {
    serviceCategories: ServiceCategoryOption[];
}) {
    const { data, setData, post, processing, errors } = useForm({
        first_name_ar: '',
        second_name_ar: '',
        family_name_ar: '',
        first_name_en: '',
        second_name_en: '',
        family_name_en: '',
        civil_id_number: '',
        civil_id_document: null as File | null,
        avatar: null as File | null,
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
        employee_code: '',
        display_name_ar: '',
        display_name_en: '',
        bio_ar: '',
        bio_en: '',
        job_title_ar: '',
        job_title_en: '',
        service_category_ids: [] as number[],
    });

    const { direction } = usePage<PageProps>().props;
    const isRtl = direction === 'rtl';
    const align = isRtl ? 'right' : 'left';
    const [attemptedSubmit, setAttemptedSubmit] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);

    const refs = {
        first_name_ar: useRef<HTMLInputElement | null>(null),
        second_name_ar: useRef<HTMLInputElement | null>(null),
        family_name_ar: useRef<HTMLInputElement | null>(null),
        civil_id_number: useRef<HTMLInputElement | null>(null),
        email: useRef<HTMLInputElement | null>(null),
        phone: useRef<HTMLInputElement | null>(null),
        password: useRef<HTMLInputElement | null>(null),
        password_confirmation: useRef<HTMLInputElement | null>(null),
    };

    const focusField = (name: keyof typeof refs) => {
        const el = refs[name].current;
        if (!el) return;
        el.scrollIntoView({ block: 'center' });
        el.focus();
    };

    const focusFirstError = (errs: Record<string, string>) => {
        const order: (keyof typeof refs)[] = [
            'first_name_ar',
            'second_name_ar',
            'family_name_ar',
            'civil_id_number',
            'email',
            'phone',
            'password',
            'password_confirmation',
        ];
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
            return;
        }
        if (errs.avatar) {
            const el = document.getElementById('avatar') as HTMLInputElement | null;
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

        if (!String(data.first_name_ar ?? '').trim()) {
            invalid.first_name_ar = __('tenant.technicians.validation_first_name_ar_required');
        }
        if (!String(data.second_name_ar ?? '').trim()) {
            invalid.second_name_ar = __('tenant.technicians.validation_second_name_ar_required');
        }
        if (!String(data.family_name_ar ?? '').trim()) {
            invalid.family_name_ar = __('tenant.technicians.validation_family_name_ar_required');
        }
        if (!civilId) {
            invalid.civil_id_number = __('tenant.technicians.validation_civil_id_number_required');
        } else if (!/^\d{12}$/.test(civilId)) {
            invalid.civil_id_number = __('tenant.technicians.validation_civil_id_number_digits');
        }
        if (!data.civil_id_document) {
            invalid.civil_id_document = __('tenant.technicians.validation_civil_id_document_required');
        }
        if (!phone) {
            invalid.phone = __('tenant.technicians.validation_phone_required');
        } else if (!/^\d{8}$/.test(phone)) {
            invalid.phone = __('tenant.technicians.phone_must_be_8_digits');
        }
        if (!String(data.password ?? '').trim()) {
            invalid.password = __('tenant.technicians.validation_password_required');
        }
        if (!String(data.password_confirmation ?? '').trim()) {
            invalid.password_confirmation = __('tenant.technicians.validation_password_confirmation_required');
        }

        return invalid;
    }, [attemptedSubmit, data.first_name_ar, data.second_name_ar, data.family_name_ar, data.civil_id_number, data.civil_id_document, data.phone, data.password, data.password_confirmation]);

    const validationMessages = useMemo(() => {
        const serverMessages = Object.values(errors).filter((message): message is string => typeof message === 'string' && message.trim() !== '');
        return Array.from(new Set([...Object.values(localErrors), ...serverMessages]));
    }, [errors, localErrors]);

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
        const onClick = (e: MouseEvent) => {
            if (!servicesMenuRef.current?.contains(e.target as Node)) setServicesOpen(false);
        };
        document.addEventListener('keydown', onKeyDown);
        document.addEventListener('mousedown', onClick);
        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.removeEventListener('mousedown', onClick);
        };
    }, [servicesOpen]);

    const selectedServiceIds = data.service_category_ids;
    const selectedServices = useMemo(
        () => serviceCategories.filter((c) => selectedServiceIds.includes(c.id)),
        [serviceCategories, selectedServiceIds],
    );
    const filteredServices = useMemo(() => {
        const q = servicesQuery.trim().toLowerCase();
        if (!q) return serviceCategories;
        return serviceCategories.filter((c) => `${c.name_ar} ${c.name_en ?? ''}`.toLowerCase().includes(q));
    }, [serviceCategories, servicesQuery]);

    const toggleService = (id: number) => {
        setData(
            'service_category_ids',
            selectedServiceIds.includes(id) ? selectedServiceIds.filter((x) => x !== id) : [...selectedServiceIds, id],
        );
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        setAttemptedSubmit(true);
        const nextLocal = (() => {
            const invalid: Record<string, string> = {};
            const phone = String(data.phone ?? '').trim();
            const civilId = String(data.civil_id_number ?? '').trim();
            if (!String(data.first_name_ar ?? '').trim()) invalid.first_name_ar = __('tenant.technicians.validation_first_name_ar_required');
            if (!String(data.second_name_ar ?? '').trim()) invalid.second_name_ar = __('tenant.technicians.validation_second_name_ar_required');
            if (!String(data.family_name_ar ?? '').trim()) invalid.family_name_ar = __('tenant.technicians.validation_family_name_ar_required');
            if (!civilId) invalid.civil_id_number = __('tenant.technicians.validation_civil_id_number_required');
            else if (!/^\d{12}$/.test(civilId)) invalid.civil_id_number = __('tenant.technicians.validation_civil_id_number_digits');
            if (!data.civil_id_document) invalid.civil_id_document = __('tenant.technicians.validation_civil_id_document_required');
            if (!phone) invalid.phone = __('tenant.technicians.validation_phone_required');
            else if (!/^\d{8}$/.test(phone)) invalid.phone = __('tenant.technicians.phone_must_be_8_digits');
            if (!String(data.password ?? '').trim()) invalid.password = __('tenant.technicians.validation_password_required');
            if (!String(data.password_confirmation ?? '').trim()) invalid.password_confirmation = __('tenant.technicians.validation_password_confirmation_required');
            return invalid;
        })();

        if (Object.keys(nextLocal).length > 0) {
            focusFirstError(nextLocal);
            return;
        }

        post(route('company.technicians.store'), {
            forceFormData: true,
            onError: (errs) => focusFirstError(errs as any),
        });
    };

    const optionalPlaceholder = (label: string, value: string, onChange: (v: string) => void, opts?: { dir?: 'ltr' | 'rtl'; id?: string }) => (
        <div className="form-group">
            <input
                id={opts?.id}
                className="form-control"
                placeholder={label}
                aria-label={label}
                dir={opts?.dir}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                style={{ textAlign: align }}
            />
        </div>
    );

    return (
        <CompanyDashboardLayout header={<h4 className="m-0 font-weight-bold text-dark">{__('tenant.technicians.createTitle')}</h4>}>
            <Head title={__('tenant.technicians.createTitle')} />

            <div className="mb-3">
                <Link href={route('company.technicians.index')} className="btn btn-outline-secondary btn-sm">
                    <i className={isRtl ? 'fas fa-arrow-right ms-1' : 'fas fa-arrow-left me-1'} />
                    {__('tenant.technicians.back')}
                </Link>
            </div>

            <TableCard title={__('tenant.technicians.createTitle')} bodyPadding>
                <form onSubmit={submit}>
                    <div className="row">
                        {validationMessages.length > 0 ? (
                            <div className="col-12">
                                <div className="alert alert-danger" role="alert">
                                    <div className="font-weight-bold mb-1">{__('tenant.technicians.validation_summary_title')}</div>
                                    <ul className="mb-0 pl-3">
                                        {validationMessages.map((message) => (
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
                            {optionalPlaceholder(__('tenant.technicians.firstNameEn'), data.first_name_en, (v) => setData('first_name_en', v), { dir: 'ltr' })}
                            {errors.first_name_en ? <div className="text-danger small mt-1 mb-3">{errors.first_name_en}</div> : null}
                        </div>
                        <div className="col-md-4">
                            {optionalPlaceholder(__('tenant.technicians.secondNameEn'), data.second_name_en, (v) => setData('second_name_en', v), { dir: 'ltr' })}
                            {errors.second_name_en ? <div className="text-danger small mt-1 mb-3">{errors.second_name_en}</div> : null}
                        </div>
                        <div className="col-md-4">
                            {optionalPlaceholder(__('tenant.technicians.familyNameEn'), data.family_name_en, (v) => setData('family_name_en', v), { dir: 'ltr' })}
                            {errors.family_name_en ? <div className="text-danger small mt-1 mb-3">{errors.family_name_en}</div> : null}
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
                                <div className="text-muted small mt-1">{__('tenant.technicians.civilIdNumberHint')}</div>
                                {errors.civil_id_number || localErrors.civil_id_number ? <div className="text-danger small mt-1">{errors.civil_id_number ?? localErrors.civil_id_number}</div> : null}
                            </div>
                        </div>
                        <div className="col-md-6">
                            <div className="form-group">
                                <label>
                                    {__('tenant.technicians.civilIdDocument')}
                                    <span className="text-danger"> *</span>
                                </label>
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
                                {avatarPreviewUrl ? (
                                    <div className="mb-2">
                                        <img
                                            src={avatarPreviewUrl}
                                            alt=""
                                            className="rounded border bg-white"
                                            style={{ width: 84, height: 84, objectFit: 'cover' }}
                                        />
                                    </div>
                                ) : null}
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
                        <div className="col-12">
                            <div className="text-muted small mb-3">
                                {__('tenant.technicians.phone_required_hint')} · {__('tenant.technicians.phone_must_be_8_digits')}
                            </div>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>
                                    {__('tenant.technicians.password')}
                                    <span className="text-danger"> *</span>
                                </label>
                                <div className="input-group">
                                    <input
                                        ref={refs.password}
                                        type={showPassword ? 'text' : 'password'}
                                        className={`form-control ${(errors.password || localErrors.password) ? 'is-invalid' : ''}`}
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                    />
                                    <div className="input-group-append">
                                        <button
                                            type="button"
                                            className="btn btn-outline-secondary"
                                            onClick={() => setShowPassword((v) => !v)}
                                            aria-label={__('tenant.technicians.togglePasswordVisibility')}
                                        >
                                            <i className={showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'} />
                                        </button>
                                    </div>
                                </div>
                                {errors.password || localErrors.password ? <div className="text-danger small mt-1">{errors.password ?? localErrors.password}</div> : null}
                            </div>
                        </div>
                        <div className="col-md-6">
                            <div className="form-group">
                                <label>
                                    {__('tenant.technicians.passwordConfirmation')}
                                    <span className="text-danger"> *</span>
                                </label>
                                <div className="input-group">
                                    <input
                                        ref={refs.password_confirmation}
                                        type={showPasswordConfirmation ? 'text' : 'password'}
                                        className={`form-control ${(errors.password_confirmation || localErrors.password_confirmation) ? 'is-invalid' : ''}`}
                                        value={data.password_confirmation}
                                        onChange={(e) => setData('password_confirmation', e.target.value)}
                                    />
                                    <div className="input-group-append">
                                        <button
                                            type="button"
                                            className="btn btn-outline-secondary"
                                            onClick={() => setShowPasswordConfirmation((v) => !v)}
                                            aria-label={__('tenant.technicians.togglePasswordVisibility')}
                                        >
                                            <i className={showPasswordConfirmation ? 'fas fa-eye-slash' : 'fas fa-eye'} />
                                        </button>
                                    </div>
                                </div>
                                {errors.password_confirmation || localErrors.password_confirmation ? <div className="text-danger small mt-1">{errors.password_confirmation ?? localErrors.password_confirmation}</div> : null}
                            </div>
                        </div>

                        <div className="col-md-6">
                            {optionalPlaceholder(__('tenant.technicians.displayNameAr'), data.display_name_ar, (v) => setData('display_name_ar', v), { dir: 'rtl' })}
                            {errors.display_name_ar ? <div className="text-danger small mt-1 mb-3">{errors.display_name_ar}</div> : null}
                        </div>
                        <div className="col-md-6">
                            {optionalPlaceholder(__('tenant.technicians.displayNameEn'), data.display_name_en, (v) => setData('display_name_en', v), { dir: 'ltr' })}
                            {errors.display_name_en ? <div className="text-danger small mt-1 mb-3">{errors.display_name_en}</div> : null}
                        </div>

                        <div className="col-md-6">
                            {optionalPlaceholder(__('tenant.technicians.employeeCode'), data.employee_code, (v) => setData('employee_code', v), { dir: 'ltr' })}
                            {errors.employee_code ? <div className="text-danger small mt-1 mb-3">{errors.employee_code}</div> : null}
                        </div>
                        <div className="col-md-6" />

                        <div className="col-md-6">
                            {optionalPlaceholder(__('tenant.technicians.jobTitleAr'), data.job_title_ar, (v) => setData('job_title_ar', v), { dir: 'rtl' })}
                            {errors.job_title_ar ? <div className="text-danger small mt-1 mb-3">{errors.job_title_ar}</div> : null}
                        </div>
                        <div className="col-md-6">
                            {optionalPlaceholder(__('tenant.technicians.jobTitleEn'), data.job_title_en, (v) => setData('job_title_en', v), { dir: 'ltr' })}
                            {errors.job_title_en ? <div className="text-danger small mt-1 mb-3">{errors.job_title_en}</div> : null}
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
                                {errors.bio_ar ? <div className="text-danger small mt-1">{errors.bio_ar}</div> : null}
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
                                {errors.bio_en ? <div className="text-danger small mt-1">{errors.bio_en}</div> : null}
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
                                                    <button
                                                        type="button"
                                                        className="btn btn-link p-0 ml-2 text-white"
                                                        style={{ textDecoration: 'none' }}
                                                        onClick={() => toggleService(s.id)}
                                                        aria-label="remove"
                                                    >
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
                                            {filteredServices.length === 0 ? <div className="text-muted small">—</div> : null}
                                        </div>
                                    ) : null}
                                </div>
                                {errors.service_category_ids ? <div className="text-danger small mt-1">{errors.service_category_ids}</div> : null}
                                {errors['service_category_ids.0'] ? <div className="text-danger small mt-1">{errors['service_category_ids.0']}</div> : null}
                            </div>
                        </div>

                        <div className="col-12">
                            <button type="submit" className="btn btn-primary px-4" disabled={processing}>
                                <i className="fas fa-save mr-1" />
                                {__('tenant.technicians.save')}
                            </button>
                        </div>
                    </div>
                </form>
            </TableCard>
        </CompanyDashboardLayout>
    );
}
