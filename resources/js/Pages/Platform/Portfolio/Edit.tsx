import '@/Components/Core/css/switch.css';
import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { PageProps } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';

type Option = { id: number; name_ar: string; name_en: string | null };
type Tech = { id: number; company_id: number; user: { name: string | null } };
type Co = { id: number; name: string };
type ImageRow = { id: number; url: string; sort_order: number };

type Project = {
    id: number;
    company_id: number;
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
    images: ImageRow[];
};

function localizedName(option: Option, locale: string): string {
    if (locale === 'en') {
        return option.name_en || option.name_ar;
    }

    return option.name_ar || option.name_en || '—';
}

function localizedTitle(project: Project, locale: string): string {
    if (locale === 'en') {
        return project.title_en || project.title_ar;
    }

    return project.title_ar || project.title_en || '—';
}

function FieldError({ error }: { error?: string }) {
    if (!error) return null;

    return <div className="text-danger small mt-1">{error}</div>;
}

function RequiredMark() {
    return <span className="text-danger"> *</span>;
}

function selectedNumberValues(select: HTMLSelectElement): number[] {
    return Array.from(select.selectedOptions, (option) => Number(option.value));
}

function selectedFileNames(files: File[]): string {
    if (files.length === 0) {
        return __('dashboard.portfolio_projects.choose_images');
    }

    return files.map((file) => file.name).join(', ');
}

export default function PlatformPortfolioEdit({
    project,
    companies,
    categories,
    technicians,
}: {
    project: Project;
    companies: Co[];
    categories: Option[];
    technicians: Tech[];
}) {
    const { data, setData, put, processing, errors } = useForm({
        company_id: project.company_id,
        title_ar: project.title_ar,
        title_en: project.title_en ?? '',
        description_ar: project.description_ar ?? '',
        description_en: project.description_en ?? '',
        location: project.location ?? '',
        completed_at: project.completed_at ?? '',
        is_featured: project.is_featured,
        is_active: project.is_active,
        service_category_id: project.service_category_id === null ? ('' as const) : project.service_category_id,
        technician_ids: project.technician_ids ?? (project.technician_id === null ? [] : [project.technician_id]),
    });

    const imageForm = useForm<{ images: File[] }>({ images: [] });
    const { locale, direction } = usePage<PageProps>().props;
    const isRtl = direction === 'rtl';
    const iconSpacingClass = isRtl ? 'ms-1' : 'me-1';
    const availableTechnicians = technicians.filter((technician) => technician.company_id === Number(data.company_id));

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('platform.portfolio-projects.update', project.id));
    };

    const uploadImage = (e: React.FormEvent) => {
        e.preventDefault();
        imageForm.post(route('platform.portfolio-projects.media.store', project.id), { forceFormData: true });
    };

    const removeImage = (id: number) => {
        if (!confirm(__('dashboard.portfolio_projects.remove_image_confirm'))) {
            return;
        }
        router.delete(route('platform.portfolio-projects.media.destroy', [project.id, id]));
    };

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.platform_portfolio"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('dashboard.portfolio_projects.edit_title')}</h4>}
        >
            <Head title={__('dashboard.portfolio_projects.edit_title')} />

            <div className="mb-3">
                <Link href={route('platform.portfolio-projects.index')} className="btn btn-outline-secondary btn-sm">
                    <i className={`${isRtl ? 'fas fa-arrow-right' : 'fas fa-arrow-left'} ${iconSpacingClass}`} />
                    {__('dashboard.actions.back')}
                </Link>
            </div>

            <div className="card card-outline card-primary">
                <div className="card-header">
                    <h3 className="card-title mb-0">{__('dashboard.portfolio_projects.form_title')}</h3>
                </div>
                <div className="card-body">
                    <form onSubmit={submit}>
                        <div className="row">
                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <label htmlFor="company_id">
                                        {__('dashboard.portfolio_projects.company')}
                                        <RequiredMark />
                                    </label>
                                    <select
                                        id="company_id"
                                        name="company_id"
                                        className={`custom-select ${errors.company_id ? 'is-invalid' : ''}`}
                                        value={String(data.company_id)}
                                        onChange={(e) => {
                                            setData({
                                                ...data,
                                                company_id: Number(e.target.value),
                                                technician_ids: [],
                                            });
                                        }}
                                    >
                                        {companies.map((company) => (
                                            <option key={company.id} value={company.id}>
                                                {company.name}
                                            </option>
                                        ))}
                                    </select>
                                    <FieldError error={errors.company_id} />
                                </div>
                            </div>

                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <label htmlFor="service_category_id">{__('dashboard.portfolio_projects.service_category')}</label>
                                    <select
                                        id="service_category_id"
                                        name="service_category_id"
                                        className={`custom-select ${errors.service_category_id ? 'is-invalid' : ''}`}
                                        value={data.service_category_id === '' ? '' : String(data.service_category_id)}
                                        onChange={(e) =>
                                            setData('service_category_id', e.target.value === '' ? '' : Number(e.target.value))
                                        }
                                    >
                                        <option value="">{__('dashboard.portfolio_projects.select_category')}</option>
                                        {categories.map((category) => (
                                            <option key={category.id} value={category.id}>
                                                {localizedName(category, locale)}
                                            </option>
                                        ))}
                                    </select>
                                    <FieldError error={errors.service_category_id} />
                                </div>
                            </div>

                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <label htmlFor="technician_ids">{__('dashboard.portfolio_projects.technicians')}</label>
                                    <select
                                        id="technician_ids"
                                        name="technician_ids[]"
                                        multiple
                                        className={`custom-select ${errors.technician_ids ? 'is-invalid' : ''}`}
                                        value={data.technician_ids.map(String)}
                                        onChange={(e) => setData('technician_ids', selectedNumberValues(e.currentTarget))}
                                    >
                                        {availableTechnicians.map((technician) => (
                                            <option key={technician.id} value={technician.id}>
                                                {technician.user.name ?? '—'}
                                            </option>
                                        ))}
                                    </select>
                                    <div className="text-muted small mt-1">{__('dashboard.portfolio_projects.select_technicians_help')}</div>
                                    <FieldError error={errors.technician_ids} />
                                </div>
                            </div>

                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <label htmlFor="completed_at">{__('dashboard.portfolio_projects.completed_at')}</label>
                                    <input
                                        id="completed_at"
                                        name="completed_at"
                                        type="date"
                                        className={`form-control ${errors.completed_at ? 'is-invalid' : ''}`}
                                        dir="ltr"
                                        value={data.completed_at}
                                        onChange={(e) => setData('completed_at', e.target.value)}
                                    />
                                    <FieldError error={errors.completed_at} />
                                </div>
                            </div>

                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <label htmlFor="title_ar">
                                        {__('dashboard.portfolio_projects.title_ar')}
                                        <RequiredMark />
                                    </label>
                                    <input
                                        id="title_ar"
                                        name="title_ar"
                                        className={`form-control ${errors.title_ar ? 'is-invalid' : ''}`}
                                        dir="rtl"
                                        value={data.title_ar}
                                        onChange={(e) => setData('title_ar', e.target.value)}
                                        style={{ textAlign: 'right' }}
                                    />
                                    <FieldError error={errors.title_ar} />
                                </div>
                            </div>

                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <label htmlFor="title_en">{__('dashboard.portfolio_projects.title_en')}</label>
                                    <input
                                        id="title_en"
                                        name="title_en"
                                        className={`form-control ${errors.title_en ? 'is-invalid' : ''}`}
                                        dir="ltr"
                                        value={data.title_en}
                                        onChange={(e) => setData('title_en', e.target.value)}
                                    />
                                    <FieldError error={errors.title_en} />
                                </div>
                            </div>

                            <div className="col-12">
                                <div className="form-group">
                                    <label htmlFor="location">{__('dashboard.portfolio_projects.location')}</label>
                                    <input
                                        id="location"
                                        name="location"
                                        className={`form-control ${errors.location ? 'is-invalid' : ''}`}
                                        value={data.location}
                                        onChange={(e) => setData('location', e.target.value)}
                                        style={{ textAlign: isRtl ? 'right' : 'left' }}
                                    />
                                    <FieldError error={errors.location} />
                                </div>
                            </div>

                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <label htmlFor="description_ar">{__('dashboard.portfolio_projects.description_ar')}</label>
                                    <textarea
                                        id="description_ar"
                                        name="description_ar"
                                        className={`form-control ${errors.description_ar ? 'is-invalid' : ''}`}
                                        dir="rtl"
                                        rows={4}
                                        value={data.description_ar}
                                        onChange={(e) => setData('description_ar', e.target.value)}
                                        style={{ textAlign: 'right' }}
                                    />
                                    <FieldError error={errors.description_ar} />
                                </div>
                            </div>

                            <div className="col-12 col-md-6">
                                <div className="form-group">
                                    <label htmlFor="description_en">{__('dashboard.portfolio_projects.description_en')}</label>
                                    <textarea
                                        id="description_en"
                                        name="description_en"
                                        className={`form-control ${errors.description_en ? 'is-invalid' : ''}`}
                                        dir="ltr"
                                        rows={4}
                                        value={data.description_en}
                                        onChange={(e) => setData('description_en', e.target.value)}
                                    />
                                    <FieldError error={errors.description_en} />
                                </div>
                            </div>

                            <div className="col-12">
                                <div className="form-group d-flex flex-wrap align-items-center">
                                    <div className={`custom-control custom-switch custom-switch-md ${isRtl ? 'ml-4' : 'mr-4'}`}>
                                        <input
                                            id="portfolio-featured"
                                            name="is_featured"
                                            type="checkbox"
                                            className="custom-control-input"
                                            checked={data.is_featured}
                                            onChange={(e) => setData('is_featured', e.target.checked)}
                                        />
                                        <label className="custom-control-label pt-1 pl-3" htmlFor="portfolio-featured">
                                            {__('dashboard.portfolio_projects.featured')}
                                        </label>
                                    </div>

                                    <div className="custom-control custom-switch custom-switch-md">
                                        <input
                                            id="portfolio-active"
                                            name="is_active"
                                            type="checkbox"
                                            className="custom-control-input"
                                            checked={data.is_active}
                                            onChange={(e) => setData('is_active', e.target.checked)}
                                        />
                                        <label className="custom-control-label pt-1 pl-3" htmlFor="portfolio-active">
                                            {__('dashboard.portfolio_projects.active')}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div className="col-12">
                                <button type="submit" disabled={processing} className="btn btn-primary px-4">
                                    <i className={`fas fa-save ${iconSpacingClass}`} />
                                    {__('dashboard.actions.save')}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div className="card card-outline card-info">
                <div className="card-header">
                    <h3 className="card-title mb-0">{__('dashboard.portfolio_projects.images_title')}</h3>
                </div>
                <div className="card-body">
                    {project.images.length > 0 ? (
                        <div className="row">
                            {project.images.map((image) => (
                                <div key={image.id} className="col-12 col-sm-6 col-lg-4">
                                    <div className="card shadow-sm">
                                        <img
                                            src={image.url}
                                            alt={localizedTitle(project, locale)}
                                            className="card-img-top"
                                            style={{ height: 180, objectFit: 'cover' }}
                                        />
                                        <div className="card-body p-2">
                                            <button
                                                type="button"
                                                className="btn btn-outline-danger btn-sm btn-block"
                                                onClick={() => removeImage(image.id)}
                                            >
                                                <i className={`fas fa-trash ${iconSpacingClass}`} />
                                                {__('dashboard.actions.remove')}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="py-4 text-center text-muted">
                            <i className="far fa-images fa-3x mb-2" />
                            <div>{__('dashboard.portfolio_projects.no_images_uploaded')}</div>
                        </div>
                    )}

                    <form onSubmit={uploadImage} className="border-top pt-3">
                        <div className="row align-items-end">
                            <div className="col-12 col-md-8">
                                <div className="form-group mb-md-0">
                                    <label htmlFor="portfolio_image">{__('dashboard.portfolio_projects.upload_image')}</label>
                                    <div className="custom-file">
                                        <input
                                            id="portfolio_image"
                                            name="images"
                                            type="file"
                                            accept="image/*"
                                            multiple
                                            className={`custom-file-input ${imageForm.errors.images ? 'is-invalid' : ''}`}
                                            onChange={(e) => imageForm.setData('images', Array.from(e.currentTarget.files ?? []))}
                                        />
                                        <label className="custom-file-label" htmlFor="portfolio_image">
                                            {selectedFileNames(imageForm.data.images)}
                                        </label>
                                    </div>
                                    <FieldError error={imageForm.errors.images} />
                                </div>
                            </div>
                            <div className="col-12 col-md-4 mt-3 mt-md-0">
                                <button
                                    type="submit"
                                    disabled={imageForm.processing || imageForm.data.images.length === 0}
                                    className="btn btn-info btn-block"
                                >
                                    <i className={`fas fa-upload ${iconSpacingClass}`} />
                                    {__('dashboard.portfolio_projects.upload_image')}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </PlatformAdminLayout>
    );
}
