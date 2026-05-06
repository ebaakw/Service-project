import '@/Components/Core/css/switch.css';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { InertiaFormProps } from '@inertiajs/react';
import { Link } from '@inertiajs/react';

export type ServiceCategoryFormData = {
    name_ar: string;
    name_en: string;
    slug: string;
    icon_path: string;
    description_ar: string;
    description_en: string;
    sort_order: number;
    is_active: boolean;
};

function FieldError({ error }: { error?: string }) {
    if (!error) return null;
    return <div className="invalid-feedback d-block">{error}</div>;
}

function RequiredMark() {
    return <span className="text-danger"> *</span>;
}

export default function ServiceCategoryForm({
    form,
    clearClientRequiredKey,
    clientRequiredErrors,
}: {
    form: InertiaFormProps<ServiceCategoryFormData>;
    clearClientRequiredKey?: (key: keyof ServiceCategoryFormData) => void;
    clientRequiredErrors?: Record<string, boolean>;
}) {
    const { data, setData, errors, processing } = form;
    const anyErrors = errors as Record<string, string | undefined>;
    const requiredClient = clientRequiredErrors ?? {};

    const isInvalid = (key: keyof ServiceCategoryFormData) =>
        Boolean(anyErrors[String(key)] || requiredClient[String(key)]);

    const fieldError = (key: keyof ServiceCategoryFormData) => {
        const server = anyErrors[String(key)];
        if (server) return server;
        return requiredClient[String(key)] ? __('tenant.field_required') : undefined;
    };

    const touch = (key: keyof ServiceCategoryFormData) => {
        clearClientRequiredKey?.(key);
    };

    return (
        <div className="card card-outline card-primary">
            <div className="card-body">
                <div className="row">
                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="name_ar">
                                {__('dashboard.forms.name_ar')}
                                <RequiredMark />
                            </label>
                            <input
                                id="name_ar"
                                name="name_ar"
                                type="text"
                                className={`form-control ${isInvalid('name_ar') ? 'is-invalid' : ''}`}
                                value={data.name_ar}
                                onChange={(e) => {
                                    setData('name_ar', e.target.value);
                                    touch('name_ar');
                                }}
                                dir="rtl"
                            />
                            <FieldError error={fieldError('name_ar')} />
                        </div>
                    </div>

                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="name_en">
                                {__('dashboard.forms.name_en')}
                                <RequiredMark />
                            </label>
                            <input
                                id="name_en"
                                name="name_en"
                                type="text"
                                className={`form-control ${isInvalid('name_en') ? 'is-invalid' : ''}`}
                                value={data.name_en}
                                onChange={(e) => {
                                    setData('name_en', e.target.value);
                                    touch('name_en');
                                }}
                                dir="ltr"
                            />
                            <FieldError error={fieldError('name_en')} />
                        </div>
                    </div>

                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="slug">
                                {__('dashboard.forms.slug')}
                                <RequiredMark />
                            </label>
                            <input
                                id="slug"
                                name="slug"
                                type="text"
                                className={`form-control ${isInvalid('slug') ? 'is-invalid' : ''}`}
                                value={data.slug}
                                onChange={(e) => {
                                    setData('slug', e.target.value);
                                    touch('slug');
                                }}
                                dir="ltr"
                            />
                            <FieldError error={fieldError('slug')} />
                        </div>
                    </div>

                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="icon_path">{__('dashboard.forms.icon_path')}</label>
                            <input
                                id="icon_path"
                                name="icon_path"
                                type="text"
                                className={`form-control ${isInvalid('icon_path') ? 'is-invalid' : ''}`}
                                value={data.icon_path}
                                onChange={(e) => setData('icon_path', e.target.value)}
                                dir="ltr"
                            />
                            <FieldError error={anyErrors.icon_path} />
                        </div>
                    </div>

                    <div className="col-12">
                        <div className="form-group">
                            <label htmlFor="description_ar">{__('dashboard.forms.description_ar')}</label>
                            <textarea
                                id="description_ar"
                                name="description_ar"
                                rows={3}
                                className={`form-control ${isInvalid('description_ar') ? 'is-invalid' : ''}`}
                                value={data.description_ar}
                                onChange={(e) => setData('description_ar', e.target.value)}
                                dir="rtl"
                            />
                            <FieldError error={anyErrors.description_ar} />
                        </div>
                    </div>

                    <div className="col-12">
                        <div className="form-group">
                            <label htmlFor="description_en">{__('dashboard.forms.description_en')}</label>
                            <textarea
                                id="description_en"
                                name="description_en"
                                rows={3}
                                className={`form-control ${isInvalid('description_en') ? 'is-invalid' : ''}`}
                                value={data.description_en}
                                onChange={(e) => setData('description_en', e.target.value)}
                                dir="ltr"
                            />
                            <FieldError error={anyErrors.description_en} />
                        </div>
                    </div>

                    <div className="col-12 col-md-6">
                        <div className="form-group">
                            <label htmlFor="sort_order">{__('dashboard.forms.sort_order')}</label>
                            <input
                                id="sort_order"
                                name="sort_order"
                                type="number"
                                min={0}
                                className={`form-control ${isInvalid('sort_order') ? 'is-invalid' : ''}`}
                                value={data.sort_order}
                                onChange={(e) => setData('sort_order', Number(e.target.value))}
                                dir="ltr"
                            />
                            <FieldError error={anyErrors.sort_order} />
                        </div>
                    </div>

                    <div className="col-12 col-md-6 d-flex align-items-end">
                        <div className="form-group w-100">
                            <div className="custom-control custom-switch custom-switch-md">
                                <input
                                    id="is_active"
                                    name="is_active"
                                    type="checkbox"
                                    className="custom-control-input"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                />
                                <label htmlFor="is_active" className="custom-control-label pt-1 pl-3">
                                    {__('dashboard.service_categories.active_label')}
                                </label>
                            </div>
                            {anyErrors.is_active ? <div className="text-danger small mt-1">{anyErrors.is_active}</div> : null}
                        </div>
                    </div>
                </div>

                <div className="d-flex justify-content-end flex-wrap mt-3">
                    <Link href={route('platform.service-categories.index')} className="btn btn-outline-secondary px-4">
                        {__('dashboard.actions.cancel')}
                    </Link>
                    <button type="submit" className="btn btn-primary px-4 ml-2" disabled={processing}>
                        <i className="fas fa-save mr-1" />
                        {__('dashboard.actions.save')}
                    </button>
                </div>
            </div>
        </div>
    );
}
