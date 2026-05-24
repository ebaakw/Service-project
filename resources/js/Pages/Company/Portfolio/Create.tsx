import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import TableCard from '@/Components/ui/TableCard';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import '@/Components/Core/css/switch.css';

type Option = { id: number; name_ar: string; name_en: string | null };
type Tech = { id: number; user: { name: string | null } };
type ErrorBag = Record<string, string | undefined>;
type RequiredField = 'images' | 'title_ar' | 'title_en' | 'service_category_id';

const REQUIRED_FIELDS: RequiredField[] = ['images', 'title_ar', 'title_en', 'service_category_id'];

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
    if (Array.isArray(value)) {
        return value.length === 0;
    }

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

export default function CompanyPortfolioCreate({
    categories,
    technicians,
}: {
    categories: Option[];
    technicians: Tech[];
}) {
    const { data, setData, post, processing, errors } = useForm({
        title_ar: '',
        title_en: '',
        description_ar: '',
        description_en: '',
        location: '',
        completed_at: '',
        is_featured: false,
        service_category_id: '' as number | '',
        technician_ids: [] as number[],
        image: null as File | null,
        images: [] as File[],
    });
    const [clientErrors, setClientErrors] = useState<ErrorBag>({});
    const { locale, direction } = usePage<PageProps>().props;
    const isRtl = direction === 'rtl';
    const iconSpacingClass = isRtl ? 'ms-1' : 'me-1';
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

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        const nextClientErrors = REQUIRED_FIELDS.reduce<ErrorBag>((nextErrors, field) => {
            const isInvalid = field === 'images'
                ? data.images.length === 0 && data.image === null
                : isEmptyRequiredValue(data[field]);

            if (isInvalid) {
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

        post(route('company.portfolio-projects.store'), {
            forceFormData: true,
            onError: (serverErrors) => focusFirstError(serverErrors as ErrorBag),
        });
    };

    return (
        <CompanyDashboardLayout
            breadcrumbKey="dashboard.nav.company_portfolio"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('tenant.portfolio_projects.create_title')}</h4>}
        >
            <Head title={__('tenant.portfolio_projects.create_title')} />

            <div className="mb-3">
                <Link href={route('company.portfolio-projects.index')} className="btn btn-outline-secondary btn-sm">
                    <i className={`${isRtl ? 'fas fa-arrow-right' : 'fas fa-arrow-left'} ${iconSpacingClass}`} />
                    {__('dashboard.actions.back')}
                </Link>
            </div>

            <TableCard title={__('tenant.portfolio_projects.create_title')} bodyPadding>
                <form onSubmit={submit}>
                    <div className="alert alert-info">
                        <i className={`fas fa-info-circle ${iconSpacingClass}`} />
                        {__('tenant.portfolio_projects.pending_notice')}
                    </div>

                    <div className="row">
                        <div className="col-md-6">
                            <div className="form-group">
                                <label>
                                    {__('tenant.portfolio_projects.single_image')}
                                    <RequiredMark />
                                </label>
                                <div className="custom-file">
                                    <input
                                        name="image"
                                        type="file"
                                        accept="image/*"
                                        className={`custom-file-input ${fieldError('images') || fieldError('image') ? 'is-invalid' : ''}`}
                                        id="portfolio-image"
                                        onChange={(event) => {
                                            setData({
                                                ...data,
                                                image: event.currentTarget.files?.[0] ?? null,
                                                images: [],
                                            });
                                            clearClientError('images');
                                        }}
                                    />
                                    <label className="custom-file-label" htmlFor="portfolio-image">
                                        {selectedFileName(data.image)}
                                    </label>
                                </div>
                                <div className="text-muted small mt-1">{__('tenant.portfolio_projects.single_image_help')}</div>
                                {fieldError('image') ? <div className="text-danger small mt-1">{fieldError('image')}</div> : null}
                            </div>
                        </div>

                        <div className="col-md-6">
                            <div className="form-group">
                                <label>
                                    {__('tenant.portfolio_projects.images')}
                                    <RequiredMark />
                                </label>
                                <div className="custom-file">
                                    <input
                                        name="images"
                                        type="file"
                                        accept="image/*"
                                        multiple
                                        className={invalidClass('custom-file-input', 'images')}
                                        id="portfolio-images"
                                        onChange={(event) => {
                                            setData({
                                                ...data,
                                                image: null,
                                                images: Array.from(event.currentTarget.files ?? []),
                                            });
                                            clearClientError('images');
                                        }}
                                    />
                                    <label className="custom-file-label" htmlFor="portfolio-images">
                                        {selectedFileNames(data.images)}
                                    </label>
                                </div>
                                <div className="text-muted small mt-1">{__('tenant.portfolio_projects.multiple_images_help')}</div>
                                {fieldError('images') ? <div className="text-danger small mt-1">{fieldError('images')}</div> : null}
                            </div>
                        </div>

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
                                    value={data.completed_at}
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
            </TableCard>
        </CompanyDashboardLayout>
    );
}
