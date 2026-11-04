import '@/Components/Core/css/switch.css';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { InertiaFormProps } from '@inertiajs/react';
import { Link } from '@inertiajs/react';

export type ContentPageFormData = {
    slug: string;
    title_ar: string;
    title_en: string;
    body_ar: string;
    body_en: string;
    is_active: boolean;
};

function FieldError({ error }: { error?: string }) {
    if (!error) return null;
    return <div className="invalid-feedback d-block">{error}</div>;
}

function RequiredMark() {
    return <span className="text-danger"> *</span>;
}

export default function ContentPageForm({
    form,
    clearClientRequiredKey,
    clientRequiredErrors,
}: {
    form: InertiaFormProps<ContentPageFormData>;
    clearClientRequiredKey?: (key: keyof ContentPageFormData) => void;
    clientRequiredErrors?: Record<string, boolean>;
}) {
    const { data, setData, errors, processing } = form;
    const anyErrors = errors as Record<string, string | undefined>;
    const requiredClient = clientRequiredErrors ?? {};

    const isInvalid = (key: keyof ContentPageFormData) =>
        Boolean(anyErrors[String(key)] || requiredClient[String(key)]);

    const fieldError = (key: keyof ContentPageFormData) => {
        const server = anyErrors[String(key)];
        if (server) return server;
        return requiredClient[String(key)] ? __('tenant.field_required') : undefined;
    };

    const touch = (key: keyof ContentPageFormData) => {
        clearClientRequiredKey?.(key);
    };

    return (
        <div className="card card-outline card-primary">
            <div className="card-body">
                <div className="row">
                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="slug">
                                {__('dashboard.content_pages.slug')}
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

                    <div className="col-12 col-lg-6 d-flex align-items-end">
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
                                    {__('dashboard.content_pages.active_label')}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="title_ar">
                                {__('dashboard.content_pages.title_ar')}
                                <RequiredMark />
                            </label>
                            <input
                                id="title_ar"
                                name="title_ar"
                                type="text"
                                className={`form-control ${isInvalid('title_ar') ? 'is-invalid' : ''}`}
                                value={data.title_ar}
                                onChange={(e) => {
                                    setData('title_ar', e.target.value);
                                    touch('title_ar');
                                }}
                                dir="rtl"
                            />
                            <FieldError error={fieldError('title_ar')} />
                        </div>
                    </div>

                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="title_en">{__('dashboard.content_pages.title_en')}</label>
                            <input
                                id="title_en"
                                name="title_en"
                                type="text"
                                className={`form-control ${isInvalid('title_en') ? 'is-invalid' : ''}`}
                                value={data.title_en}
                                onChange={(e) => setData('title_en', e.target.value)}
                                dir="ltr"
                            />
                            <FieldError error={anyErrors.title_en} />
                        </div>
                    </div>

                    <div className="col-12">
                        <div className="form-group">
                            <label htmlFor="body_ar">{__('dashboard.content_pages.body_ar')}</label>
                            <textarea
                                id="body_ar"
                                name="body_ar"
                                rows={6}
                                className={`form-control ${isInvalid('body_ar') ? 'is-invalid' : ''}`}
                                value={data.body_ar}
                                onChange={(e) => setData('body_ar', e.target.value)}
                                dir="rtl"
                            />
                            <FieldError error={anyErrors.body_ar} />
                        </div>
                    </div>

                    <div className="col-12">
                        <div className="form-group">
                            <label htmlFor="body_en">{__('dashboard.content_pages.body_en')}</label>
                            <textarea
                                id="body_en"
                                name="body_en"
                                rows={6}
                                className={`form-control ${isInvalid('body_en') ? 'is-invalid' : ''}`}
                                value={data.body_en}
                                onChange={(e) => setData('body_en', e.target.value)}
                                dir="ltr"
                            />
                            <FieldError error={anyErrors.body_en} />
                        </div>
                    </div>
                </div>

                <div className="d-flex justify-content-end flex-wrap mt-3">
                    <Link href={route('platform.content-pages.index')} className="btn btn-outline-secondary px-4">
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
