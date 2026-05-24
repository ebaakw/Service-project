import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import StatusBadge from '@/Components/ui/StatusBadge';
import TableCard from '@/Components/ui/TableCard';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { PageProps } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import '@/Components/Core/css/switch.css';

type Option = { id: number; name_ar: string; name_en: string | null };
type Tech = { id: number; user: { name: string | null } };
type ImageRow = { id: number; url: string; sort_order: number };
type ErrorBag = Record<string, string | undefined>;
type RequiredField = 'title_ar' | 'title_en' | 'service_category_id';

const REQUIRED_FIELDS: RequiredField[] = ['title_ar', 'title_en', 'service_category_id'];

type Project = {
    id: number;
    title_ar: string;
    title_en: string | null;
    description_ar: string | null;
    description_en: string | null;
    location: string | null;
    completed_at: string | null;
    is_featured: boolean;
    is_active: boolean;
    service_category_id: number | null;
    technician_id: number | null;
    technician_ids: number[];
    review_status: string | null;
    reviewed_at: string | null;
    rejection_reason: string | null;
    images: ImageRow[];
    can: {
        update: boolean;
        delete: boolean;
        upload_images: boolean;
    };
};

function localizedName(option: Option, locale: string): string {
    if (locale === 'en') {
        return option.name_en || option.name_ar;
    }

    return option.name_ar || option.name_en || '—';
}

function selectedNumberValues(select: HTMLSelectElement): number[] {
    return Array.from(select.selectedOptions, (option) => Number(option.value));
}

function selectedFileNames(files: File[]): string {
    if (files.length === 0) {
        return __('tenant.choose_files');
    }

    return files.map((file) => file.name).join(', ');
}

function selectedFileName(file: File | null): string {
    return file?.name ?? __('tenant.choose_file');
}

function RequiredMark() {
    return <span className="text-danger"> *</span>;
}

function isEmptyRequiredValue(value: unknown): boolean {
    return value === null || value === undefined || (typeof value === 'string' && value.trim() === '') || value === '';
}

function focusField(field: string) {
    window.requestAnimationFrame(() => {
        const element = document.querySelector<HTMLElement>(`[name="${field}"]`);

        if (!element) {
            return;
        }

        element.scrollIntoView({ block: 'center' });
        element.focus({ preventScroll: true });
    });
}

function focusFirstError(errors: ErrorBag) {
    const firstField = Object.keys(errors)[0]?.split('.')[0];

    if (firstField) {
        focusField(firstField);
    }
}

export default function CompanyPortfolioEdit({
    project,
    categories,
    technicians,
}: {
    project: Project;
    categories: Option[];
    technicians: Tech[];
}) {
    const { data, setData, put, processing, errors } = useForm({
        title_ar: project.title_ar,
        title_en: project.title_en ?? '',
        description_ar: project.description_ar ?? '',
        description_en: project.description_en ?? '',
        location: project.location ?? '',
        completed_at: project.completed_at ?? '',
        is_featured: project.is_featured,
        service_category_id: project.service_category_id === null ? ('' as const) : project.service_category_id,
        technician_ids: project.technician_ids ?? (project.technician_id === null ? [] : [project.technician_id]),
    });
    const [clientErrors, setClientErrors] = useState<ErrorBag>({});
    const page = usePage<PageProps>().props;
    const { locale, direction } = page;
    const statusLabels = page.enumLabels?.portfolio_project_review_status ?? {};
    const isRtl = direction === 'rtl';
    const iconSpacingClass = isRtl ? 'ms-1' : 'me-1';
    const canUpdateProject = project.can.update;
    const canUploadImages = project.can.upload_images;
    const fieldError = (field: string) => clientErrors[field] ?? (errors as ErrorBag)[field] ?? (errors as ErrorBag)[`${field}.0`];
    const invalidClass = (baseClass: string, field: string) => `${baseClass}${fieldError(field) ? ' is-invalid' : ''}`;
    const clearClientError = (field: string) => {
        if (!clientErrors[field]) {
            return;
        }

        setClientErrors((current) => {
            const next = { ...current };
            delete next[field];

            return next;
        });
    };

    const imageForm = useForm<{ image: File | null; images: File[]; sort_order: string }>({ image: null, images: [], sort_order: '' });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        const nextClientErrors = REQUIRED_FIELDS.reduce<ErrorBag>((nextErrors, field) => {
            if (isEmptyRequiredValue(data[field])) {
                nextErrors[field] = __('tenant.field_required');
            }

            return nextErrors;
        }, {});
        setClientErrors(nextClientErrors);

        const firstInvalidField = REQUIRED_FIELDS.find((field) => nextClientErrors[field]);
        if (firstInvalidField) {
            focusField(firstInvalidField);
            return;
        }

        put(route('company.portfolio-projects.update', project.id), {
            onError: (serverErrors) => focusFirstError(serverErrors as ErrorBag),
        });
    };

    const uploadImage = (e: React.FormEvent) => {
        e.preventDefault();
        imageForm.post(route('company.portfolio-projects.media.store', project.id), { forceFormData: true });
    };

    const removeImage = (id: number) => {
        if (!confirm(__('tenant.portfolio_projects.remove_image_confirm'))) {
            return;
        }
        router.delete(route('company.portfolio-projects.media.destroy', [project.id, id]));
    };

    const statusBadge = () => {
        const value = (project.review_status ?? '').toLowerCase();
        if (value === 'approved') return <StatusBadge variant="success">{statusLabels.approved ?? value}</StatusBadge>;
        if (value === 'pending') return <StatusBadge variant="warning">{statusLabels.pending ?? value}</StatusBadge>;
        if (value === 'rejected') return <StatusBadge variant="danger">{statusLabels.rejected ?? value}</StatusBadge>;
        return <StatusBadge variant="secondary">—</StatusBadge>;
    };

    return (
        <CompanyDashboardLayout
            breadcrumbKey="dashboard.nav.company_portfolio"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('tenant.portfolio_projects.edit_title')}</h4>}
        >
            <Head title={__('tenant.portfolio_projects.edit_title')} />

            <div className="mb-3">
                <Link href={route('company.portfolio-projects.index')} className="btn btn-outline-secondary btn-sm">
                    <i className={`${isRtl ? 'fas fa-arrow-right' : 'fas fa-arrow-left'} ${iconSpacingClass}`} />
                    {__('dashboard.actions.back')}
                </Link>
            </div>

            <TableCard title={__('tenant.portfolio_projects.review_status')} bodyPadding>
                <div className="d-flex flex-wrap align-items-center">
                    <span className={iconSpacingClass}>{statusBadge()}</span>
                    {project.reviewed_at ? (
                        <span className="text-muted small" dir="ltr">
                            {new Date(project.reviewed_at).toLocaleString()}
                        </span>
                    ) : null}
                </div>
                {project.rejection_reason ? (
                    <div className="alert alert-warning mt-3 mb-0">
                        <div className="font-weight-bold">{__('tenant.portfolio_projects.rejection_reason')}</div>
                        <div>{project.rejection_reason}</div>
                    </div>
                ) : null}
            </TableCard>

            {canUpdateProject ? <TableCard title={__('tenant.portfolio_projects.edit_title')} bodyPadding>
                <form onSubmit={submit}>
                    <div className="alert alert-info">
                        <i className={`fas fa-info-circle ${iconSpacingClass}`} />
                        {__('tenant.portfolio_projects.edit_pending_notice')}
                    </div>

                    <div className="row">
                        <div className="col-md-6">
                            <div className="form-group">
                                <label>
                                    {__('dashboard.forms.title_ar')}
                                    <RequiredMark />
                                </label>
                                <input
                                    name="title_ar"
                                    className={invalidClass('form-control', 'title_ar')}
                                    dir="rtl"
                                    value={data.title_ar}
                                    onChange={(event) => {
                                        setData('title_ar', event.target.value);
                                        clearClientError('title_ar');
                                    }}
                                    style={{ textAlign: 'right' }}
                                />
                                {fieldError('title_ar') ? <div className="text-danger small mt-1">{fieldError('title_ar')}</div> : null}
                            </div>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>
                                    {__('dashboard.forms.title_en')}
                                    <RequiredMark />
                                </label>
                                <input
                                    name="title_en"
                                    className={invalidClass('form-control', 'title_en')}
                                    dir="ltr"
                                    value={data.title_en}
                                    onChange={(event) => {
                                        setData('title_en', event.target.value);
                                        clearClientError('title_en');
                                    }}
                                />
                                {fieldError('title_en') ? <div className="text-danger small mt-1">{fieldError('title_en')}</div> : null}
                            </div>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>
                                    {__('tenant.portfolio_projects.category')}
                                    <RequiredMark />
                                </label>
                                <select
                                    name="service_category_id"
                                    className={invalidClass('custom-select', 'service_category_id')}
                                    value={data.service_category_id === '' ? '' : String(data.service_category_id)}
                                    onChange={(event) => {
                                        setData('service_category_id', event.target.value === '' ? '' : Number(event.target.value));
                                        clearClientError('service_category_id');
                                    }}
                                >
                                    <option value="">{__('tenant.select_category')}</option>
                                    {categories.map((category) => (
                                        <option key={category.id} value={category.id}>
                                            {localizedName(category, locale)}
                                        </option>
                                    ))}
                                </select>
                                {fieldError('service_category_id') ? (
                                    <div className="text-danger small mt-1">{fieldError('service_category_id')}</div>
                                ) : null}
                            </div>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.portfolio_projects.technicians')}</label>
                                <select
                                    name="technician_ids"
                                    multiple
                                    className={invalidClass('custom-select', 'technician_ids')}
                                    value={data.technician_ids.map(String)}
                                    onChange={(event) => setData('technician_ids', selectedNumberValues(event.currentTarget))}
                                >
                                    {technicians.map((technician) => (
                                        <option key={technician.id} value={technician.id}>
                                            {technician.user.name ?? '—'}
                                        </option>
                                    ))}
                                </select>
                                <div className="text-muted small mt-1">{__('tenant.portfolio_projects.select_technicians_help')}</div>
                                {fieldError('technician_ids') ? <div className="text-danger small mt-1">{fieldError('technician_ids')}</div> : null}
                            </div>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.portfolio_projects.completed_at')}</label>
                                <input
                                    name="completed_at"
                                    type="date"
                                    className={invalidClass('form-control', 'completed_at')}
                                    dir="ltr"
                                    value={data.completed_at ?? ''}
                                    onChange={(event) => setData('completed_at', event.target.value)}
                                />
                                {fieldError('completed_at') ? <div className="text-danger small mt-1">{fieldError('completed_at')}</div> : null}
                            </div>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('tenant.portfolio_projects.location')}</label>
                                <input
                                    name="location"
                                    className={invalidClass('form-control', 'location')}
                                    dir="rtl"
                                    value={data.location}
                                    onChange={(event) => setData('location', event.target.value)}
                                    style={{ textAlign: 'right' }}
                                />
                                {fieldError('location') ? <div className="text-danger small mt-1">{fieldError('location')}</div> : null}
                            </div>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('dashboard.forms.description_ar')}</label>
                                <textarea
                                    name="description_ar"
                                    className={invalidClass('form-control', 'description_ar')}
                                    dir="rtl"
                                    rows={4}
                                    value={data.description_ar}
                                    onChange={(event) => setData('description_ar', event.target.value)}
                                    style={{ textAlign: 'right' }}
                                />
                                {fieldError('description_ar') ? <div className="text-danger small mt-1">{fieldError('description_ar')}</div> : null}
                            </div>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>{__('dashboard.forms.description_en')}</label>
                                <textarea
                                    name="description_en"
                                    className={invalidClass('form-control', 'description_en')}
                                    dir="ltr"
                                    rows={4}
                                    value={data.description_en}
                                    onChange={(event) => setData('description_en', event.target.value)}
                                />
                                {fieldError('description_en') ? <div className="text-danger small mt-1">{fieldError('description_en')}</div> : null}
                            </div>
                        </div>

                        <div className="col-12">
                            <div className="form-group">
                                <div className="custom-control custom-switch custom-switch-md">
                                    <input
                                        name="is_featured"
                                        type="checkbox"
                                        id="portfolio-featured"
                                        className="custom-control-input"
                                        checked={data.is_featured}
                                        onChange={(event) => setData('is_featured', event.target.checked)}
                                    />
                                    <label className="custom-control-label pt-1 pl-3" htmlFor="portfolio-featured">
                                        {__('dashboard.portfolio_projects.featured')}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div className="col-12">
                            <button type="submit" disabled={processing} className="btn btn-primary px-4">
                                <i className={`fas fa-save ${iconSpacingClass}`} />
                                {processing ? __('tenant.loading') : __('dashboard.actions.save')}
                            </button>
                        </div>
                    </div>
                </form>
            </TableCard> : null}

            <TableCard title={__('tenant.portfolio_projects.images')} bodyPadding>
                <div className="row">
                    {project.images.map((image) => (
                        <div key={image.id} className="col-sm-6 col-md-4 col-lg-3">
                            <div className="card">
                                <img src={image.url} alt="" className="card-img-top" style={{ height: 150, objectFit: 'cover' }} />
                                {canUploadImages ? (
                                    <div className="card-body p-2">
                                        <button type="button" className="btn btn-outline-danger btn-sm btn-block" onClick={() => removeImage(image.id)}>
                                            <i className={`fas fa-trash ${iconSpacingClass}`} />
                                            {__('dashboard.actions.remove')}
                                        </button>
                                    </div>
                                ) : null}
                            </div>
                        </div>
                    ))}
                    {project.images.length === 0 ? (
                        <div className="col-12">
                            <div className="text-muted text-center py-4">{__('dashboard.portfolio_projects.no_image')}</div>
                        </div>
                    ) : null}
                </div>

                {canUploadImages ? <form onSubmit={uploadImage} className="border-top pt-3">
                    <div className="row align-items-end">
                        <div className="col-md-4">
                            <div className="form-group mb-md-0">
                                <label>{__('tenant.portfolio_projects.single_image')}</label>
                                <div className="custom-file">
                                    <input
                                        name="image"
                                        type="file"
                                        accept="image/*"
                                        className={`custom-file-input ${imageForm.errors.image ? 'is-invalid' : ''}`}
                                        id="portfolio-extra-single-image"
                                        onChange={(event) =>
                                            imageForm.setData({
                                                ...imageForm.data,
                                                image: event.currentTarget.files?.[0] ?? null,
                                                images: [],
                                            })
                                        }
                                    />
                                    <label className="custom-file-label" htmlFor="portfolio-extra-single-image">
                                        {selectedFileName(imageForm.data.image)}
                                    </label>
                                </div>
                                <div className="text-muted small mt-1">{__('tenant.portfolio_projects.single_image_help')}</div>
                                {imageForm.errors.image ? <div className="text-danger small mt-1">{imageForm.errors.image}</div> : null}
                            </div>
                        </div>

                        <div className="col-md-5 mt-3 mt-md-0">
                            <div className="form-group mb-md-0">
                                <label>{__('tenant.portfolio_projects.add_image')}</label>
                                <div className="custom-file">
                                    <input
                                        name="images"
                                        type="file"
                                        accept="image/*"
                                        multiple
                                        className={`custom-file-input ${imageForm.errors.images ? 'is-invalid' : ''}`}
                                        id="portfolio-extra-image"
                                        onChange={(event) =>
                                            imageForm.setData({
                                                ...imageForm.data,
                                                image: null,
                                                images: Array.from(event.currentTarget.files ?? []),
                                            })
                                        }
                                    />
                                    <label className="custom-file-label" htmlFor="portfolio-extra-image">
                                        {selectedFileNames(imageForm.data.images)}
                                    </label>
                                </div>
                                <div className="text-muted small mt-1">{__('tenant.portfolio_projects.multiple_images_help')}</div>
                                {imageForm.errors.images ? <div className="text-danger small mt-1">{imageForm.errors.images}</div> : null}
                            </div>
                        </div>
                        <div className="col-md-3 mt-3 mt-md-0">
                            <button
                                type="submit"
                                disabled={imageForm.processing || (imageForm.data.images.length === 0 && imageForm.data.image === null)}
                                className="btn btn-secondary btn-block"
                            >
                                <i className={`fas fa-upload ${iconSpacingClass}`} />
                                {imageForm.processing ? __('tenant.loading') : __('tenant.portfolio_projects.upload_image')}
                            </button>
                        </div>
                    </div>
                </form> : null}
            </TableCard>
        </CompanyDashboardLayout>
    );
}
