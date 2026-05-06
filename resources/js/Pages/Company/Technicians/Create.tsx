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
        name_ar: '',
        name_en: '',
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
    const [attemptedSubmit, setAttemptedSubmit] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);

    const refs = {
        name_ar: useRef<HTMLInputElement | null>(null),
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
        const first = Object.keys(errs)[0] as keyof typeof refs | undefined;
        if (first && first in refs) focusField(first);
        else if (errs.name_ar) focusField('name_ar');
        else if (errs.email || errs.phone) focusField('email');
        else if (errs.password) focusField('password');
        else if (errs.avatar) {
            const el = document.getElementById('avatar') as HTMLInputElement | null;
            if (el) {
                el.scrollIntoView({ block: 'center' });
                el.focus();
            }
        }
    };

    const localInvalid = useMemo(() => {
        if (!attemptedSubmit) return {};
        const invalid: Record<string, boolean> = {};
        invalid.name_ar = !String(data.name_ar ?? '').trim();
        invalid.email = !String(data.email ?? '').trim() && !String(data.phone ?? '').trim();
        invalid.phone = !String(data.email ?? '').trim() && !String(data.phone ?? '').trim();
        invalid.password = !String(data.password ?? '').trim();
        invalid.password_confirmation = !String(data.password_confirmation ?? '').trim();
        return invalid;
    }, [attemptedSubmit, data]);

    const avatarPreviewUrl = useMemo(() => safeObjectUrl(data.avatar), [data.avatar]);
    useEffect(() => {
        return () => {
            if (avatarPreviewUrl) URL.revokeObjectURL(avatarPreviewUrl);
        };
    }, [avatarPreviewUrl]);

    // ---- Multi-select services (badges + checkbox menu) ----
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

    const filteredServices = useMemo(() => {
        const q = servicesQuery.trim().toLowerCase();
        if (!q) return serviceCategories;
        return serviceCategories.filter((c) => {
            const hay = `${c.name_ar ?? ''} ${c.name_en ?? ''}`.toLowerCase();
            return hay.includes(q);
        });
    }, [servicesQuery, serviceCategories]);

    const toggleService = (id: number) => {
        const exists = selectedServiceIds.includes(id);
        const next = exists ? selectedServiceIds.filter((x) => x !== id) : [...selectedServiceIds, id];
        setData('service_category_ids', next);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        setAttemptedSubmit(true);
        if (!String(data.name_ar ?? '').trim()) {
            focusField('name_ar');
            return;
        }
        if (!String(data.email ?? '').trim() && !String(data.phone ?? '').trim()) {
            focusField('email');
            return;
        }
        if (!String(data.password ?? '').trim()) {
            focusField('password');
            return;
        }
        if (!String(data.password_confirmation ?? '').trim()) {
            focusField('password_confirmation');
            return;
        }

        post(route('company.technicians.store'), {
            forceFormData: true,
            onError: (errs) => focusFirstError(errs as any),
        });
    };

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
                        <div className="col-md-6">
                            <div className="form-group">
                                <label>
                                    {__('tenant.technicians.userNameAr')}
                                    <span className="text-danger"> *</span>
                                </label>
                                <input
                                    ref={refs.name_ar}
                                    className={`form-control ${(errors.name_ar || localInvalid.name_ar) ? 'is-invalid' : ''}`}
                                    dir="rtl"
                                    value={data.name_ar}
                                    onChange={(e) => setData('name_ar', e.target.value)}
                                    style={{ textAlign: 'right' }}
                                />
                                {errors.name_ar ? <div className="text-danger small mt-1">{errors.name_ar}</div> : null}
                            </div>
                        </div>
                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.technicians.userNameEn')}</label>
                                <input
                                    className="form-control"
                                    dir="ltr"
                                    value={data.name_en}
                                    onChange={(e) => setData('name_en', e.target.value)}
                                />
                                {errors.name_en ? <div className="text-danger small mt-1">{errors.name_en}</div> : null}
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
                                <label>
                                    {__('tenant.technicians.email')}
                                    <span className="text-danger"> *</span>
                                </label>
                                <input
                                    ref={refs.email}
                                    className={`form-control ${(errors.email || localInvalid.email) ? 'is-invalid' : ''}`}
                                    type="email"
                                    dir="ltr"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
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
                                    className={`form-control ${(errors.phone || localInvalid.phone) ? 'is-invalid' : ''}`}
                                    dir="ltr"
                                    value={data.phone}
                                    onChange={(e) => setData('phone', e.target.value)}
                                />
                                {errors.phone ? <div className="text-danger small mt-1">{errors.phone}</div> : null}
                            </div>
                        </div>
                        <div className="col-12">
                            <div className="text-muted small mb-3">
                                {__('tenant.technicians.emailOrPhoneHint')} · {__('tenant.technicians.phone_must_be_8_digits')}
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
                                        className={`form-control ${(errors.password || localInvalid.password) ? 'is-invalid' : ''}`}
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
                                {errors.password ? <div className="text-danger small mt-1">{errors.password}</div> : null}
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
                                        className={`form-control ${(errors.password_confirmation || localInvalid.password_confirmation) ? 'is-invalid' : ''}`}
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
                            </div>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.technicians.displayNameAr')}</label>
                                <input className="form-control" dir="rtl" value={data.display_name_ar} onChange={(e) => setData('display_name_ar', e.target.value)} style={{ textAlign: 'right' }} />
                                {errors.display_name_ar ? <div className="text-danger small mt-1">{errors.display_name_ar}</div> : null}
                            </div>
                        </div>
                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.technicians.displayNameEn')}</label>
                                <input className="form-control" dir="ltr" value={data.display_name_en} onChange={(e) => setData('display_name_en', e.target.value)} />
                                {errors.display_name_en ? <div className="text-danger small mt-1">{errors.display_name_en}</div> : null}
                            </div>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.technicians.employeeCode')}</label>
                                <input className="form-control" dir="ltr" value={data.employee_code} onChange={(e) => setData('employee_code', e.target.value)} />
                                {errors.employee_code ? <div className="text-danger small mt-1">{errors.employee_code}</div> : null}
                            </div>
                        </div>
                        <div className="col-md-6" />

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.technicians.jobTitleAr')}</label>
                                <input className="form-control" dir="rtl" value={data.job_title_ar} onChange={(e) => setData('job_title_ar', e.target.value)} style={{ textAlign: 'right' }} />
                                {errors.job_title_ar ? <div className="text-danger small mt-1">{errors.job_title_ar}</div> : null}
                            </div>
                        </div>
                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.technicians.jobTitleEn')}</label>
                                <input className="form-control" dir="ltr" value={data.job_title_en} onChange={(e) => setData('job_title_en', e.target.value)} />
                                {errors.job_title_en ? <div className="text-danger small mt-1">{errors.job_title_en}</div> : null}
                            </div>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.technicians.bioAr')}</label>
                                <textarea className="form-control" dir="rtl" rows={3} value={data.bio_ar} onChange={(e) => setData('bio_ar', e.target.value)} style={{ textAlign: 'right' }} />
                                {errors.bio_ar ? <div className="text-danger small mt-1">{errors.bio_ar}</div> : null}
                            </div>
                        </div>
                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.technicians.bioEn')}</label>
                                <textarea className="form-control" dir="ltr" rows={3} value={data.bio_en} onChange={(e) => setData('bio_en', e.target.value)} />
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
                                                value={servicesQuery}
                                                onChange={(e) => setServicesQuery(e.target.value)}
                                                style={{ textAlign: isRtl ? 'right' : 'left' }}
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
