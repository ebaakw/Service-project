import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import TableCard from '@/Components/ui/TableCard';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { PageProps } from '@/types';
import '@/Components/Core/css/switch.css';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

type ServiceCategoryOption = { id: number; name_ar: string; name_en: string | null };

type TechnicianPayload = {
    id: number;
    employee_code: string | null;
    display_name_ar: string | null;
    display_name_en: string | null;
    bio_ar: string | null;
    bio_en: string | null;
    job_title_ar: string | null;
    job_title_en: string | null;
    is_active: boolean;
    user: { name: string; name_ar?: string | null; name_en?: string | null; email: string | null; phone: string | null };
    company: { id: number; name: string };
    service_category_ids: number[];
};

export default function PlatformTechniciansEdit({
    technician,
    serviceCategories,
}: {
    technician: TechnicianPayload;
    serviceCategories: ServiceCategoryOption[];
}) {
    const { direction } = usePage<PageProps>().props;
    const isRtl = direction === 'rtl';

    const { data, setData, patch, processing, errors } = useForm({
        name_ar: technician.user.name_ar ?? technician.user.name,
        name_en: technician.user.name_en ?? '',
        email: technician.user.email ?? '',
        phone: technician.user.phone ?? '',
        password: '',
        password_confirmation: '',
        employee_code: technician.employee_code ?? '',
        display_name_ar: technician.display_name_ar ?? '',
        display_name_en: technician.display_name_en ?? '',
        bio_ar: technician.bio_ar ?? '',
        bio_en: technician.bio_en ?? '',
        job_title_ar: technician.job_title_ar ?? '',
        job_title_en: technician.job_title_en ?? '',
        is_active: technician.is_active,
        service_category_ids: technician.service_category_ids,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(route('platform.technicians.update', technician.id));
    };

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.all_technicians"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('tenant.technicians.editTitle')}</h4>}
        >
            <Head title={__('tenant.technicians.editTitle')} />

            <div className="mb-3">
                <Link href={route('platform.technicians.show', technician.id)} className="btn btn-outline-secondary btn-sm">
                    <i className={isRtl ? 'fas fa-arrow-right ms-1' : 'fas fa-arrow-left me-1'} />
                    {__('tenant.technicians.backToProfile')}
                </Link>
            </div>

            <TableCard title={__('tenant.technicians.editTitle')} bodyPadding>
                <form onSubmit={submit}>
                    <div className="row">
                        <div className="col-12">
                            <div className="callout callout-info mb-3">
                                <strong>{__('tenant.technicians.company')}</strong>: {technician.company.name}
                            </div>
                        </div>

                        <div className="col-12">
                            <h5 className="mb-3">{__('tenant.technicians.userSection')}</h5>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.technicians.userNameAr')}</label>
                                <input
                                    className="form-control"
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

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.technicians.email')}</label>
                                <input
                                    className="form-control"
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
                                <label>{__('tenant.technicians.phone')}</label>
                                <input
                                    className="form-control"
                                    dir="ltr"
                                    value={data.phone}
                                    onChange={(e) => setData('phone', e.target.value)}
                                />
                                {errors.phone ? <div className="text-danger small mt-1">{errors.phone}</div> : null}
                            </div>
                        </div>

                        <div className="col-12">
                            <div className="text-muted small mb-3">{__('tenant.technicians.emailOrPhoneHint')}</div>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.technicians.newPassword')}</label>
                                <input
                                    className="form-control"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                />
                                {errors.password ? <div className="text-danger small mt-1">{errors.password}</div> : null}
                            </div>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.technicians.newPasswordConfirmation')}</label>
                                <input
                                    className="form-control"
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                />
                            </div>
                        </div>

                        <div className="col-12">
                            <h5 className="mb-3 mt-2">{__('tenant.technicians.profileSection')}</h5>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.technicians.displayNameAr')}</label>
                                <input
                                    className="form-control"
                                    dir="rtl"
                                    value={data.display_name_ar}
                                    onChange={(e) => setData('display_name_ar', e.target.value)}
                                    style={{ textAlign: 'right' }}
                                />
                                {errors.display_name_ar ? <div className="text-danger small mt-1">{errors.display_name_ar}</div> : null}
                            </div>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.technicians.displayNameEn')}</label>
                                <input
                                    className="form-control"
                                    dir="ltr"
                                    value={data.display_name_en}
                                    onChange={(e) => setData('display_name_en', e.target.value)}
                                />
                                {errors.display_name_en ? <div className="text-danger small mt-1">{errors.display_name_en}</div> : null}
                            </div>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.technicians.employeeCode')}</label>
                                <input
                                    className="form-control"
                                    dir="ltr"
                                    value={data.employee_code}
                                    onChange={(e) => setData('employee_code', e.target.value)}
                                />
                                {errors.employee_code ? <div className="text-danger small mt-1">{errors.employee_code}</div> : null}
                            </div>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.technicians.categories')}</label>
                                <select
                                    multiple
                                    size={Math.min(10, Math.max(4, serviceCategories.length || 4))}
                                    className="custom-select"
                                    value={data.service_category_ids.map(String)}
                                    onChange={(e) => {
                                        const selected = Array.from(e.target.selectedOptions).map((o) => Number(o.value));
                                        setData('service_category_ids', selected);
                                    }}
                                    style={{ textAlign: isRtl ? 'right' : 'left' }}
                                >
                                    {serviceCategories.map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.name_ar}
                                            {c.name_en ? ` — ${c.name_en}` : ''}
                                        </option>
                                    ))}
                                </select>
                                {errors.service_category_ids ? <div className="text-danger small mt-1">{errors.service_category_ids}</div> : null}
                                {errors['service_category_ids.0'] ? <div className="text-danger small mt-1">{errors['service_category_ids.0']}</div> : null}
                            </div>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.technicians.jobTitleAr')}</label>
                                <input
                                    className="form-control"
                                    dir="rtl"
                                    value={data.job_title_ar}
                                    onChange={(e) => setData('job_title_ar', e.target.value)}
                                    style={{ textAlign: 'right' }}
                                />
                                {errors.job_title_ar ? <div className="text-danger small mt-1">{errors.job_title_ar}</div> : null}
                            </div>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.technicians.jobTitleEn')}</label>
                                <input
                                    className="form-control"
                                    dir="ltr"
                                    value={data.job_title_en}
                                    onChange={(e) => setData('job_title_en', e.target.value)}
                                />
                                {errors.job_title_en ? <div className="text-danger small mt-1">{errors.job_title_en}</div> : null}
                            </div>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.technicians.bioAr')}</label>
                                <textarea
                                    className="form-control"
                                    dir="rtl"
                                    rows={3}
                                    value={data.bio_ar}
                                    onChange={(e) => setData('bio_ar', e.target.value)}
                                    style={{ textAlign: 'right' }}
                                />
                                {errors.bio_ar ? <div className="text-danger small mt-1">{errors.bio_ar}</div> : null}
                            </div>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.technicians.bioEn')}</label>
                                <textarea
                                    className="form-control"
                                    dir="ltr"
                                    rows={3}
                                    value={data.bio_en}
                                    onChange={(e) => setData('bio_en', e.target.value)}
                                />
                                {errors.bio_en ? <div className="text-danger small mt-1">{errors.bio_en}</div> : null}
                            </div>
                        </div>

                        <div className="col-12">
                            <div className="form-group">
                                <div className="custom-control custom-switch custom-switch-md">
                                    <input
                                        id="is_active"
                                        type="checkbox"
                                        className="custom-control-input"
                                        checked={data.is_active}
                                        onChange={(e) => setData('is_active', e.target.checked)}
                                    />
                                    <label htmlFor="is_active" className="custom-control-label pt-1 pl-3">
                                        {__('tenant.technicians.active')}
                                    </label>
                                </div>
                                {errors.is_active ? <div className="text-danger small mt-1">{errors.is_active}</div> : null}
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
        </PlatformAdminLayout>
    );
}
