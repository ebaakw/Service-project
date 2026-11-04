import '@/Components/Core/css/switch.css';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { InertiaFormProps } from '@inertiajs/react';
import { Link } from '@inertiajs/react';

export type BannerFormData = {
    title_ar: string;
    title_en: string;
    subtitle_ar: string;
    subtitle_en: string;
    cta_label_ar: string;
    cta_label_en: string;
    image: File | null;
    link_type: string;
    link_value: string;
    sort_order: number;
    is_active: boolean;
    starts_at: string;
    ends_at: string;
};

function FieldError({ error }: { error?: string }) {
    if (!error) return null;
    return <div className="invalid-feedback d-block">{error}</div>;
}

function RequiredMark() {
    return <span className="text-danger"> *</span>;
}

export default function BannerForm({
    form,
    linkTypes,
    imageUrl,
    clearClientRequiredKey,
    clientRequiredErrors,
}: {
    form: InertiaFormProps<BannerFormData>;
    linkTypes: string[];
    imageUrl?: string | null;
    clearClientRequiredKey?: (key: keyof BannerFormData) => void;
    clientRequiredErrors?: Record<string, boolean>;
}) {
    const { data, setData, errors, processing } = form;
    const anyErrors = errors as Record<string, string | undefined>;
    const requiredClient = clientRequiredErrors ?? {};

    const isInvalid = (key: keyof BannerFormData) =>
        Boolean(anyErrors[String(key)] || requiredClient[String(key)]);

    const fieldError = (key: keyof BannerFormData) => {
        const server = anyErrors[String(key)];
        if (server) return server;
        return requiredClient[String(key)] ? __('tenant.field_required') : undefined;
    };

    const touch = (key: keyof BannerFormData) => {
        clearClientRequiredKey?.(key);
    };

    return (
        <div className="card card-outline card-primary">
            <div className="card-body">
                <div className="row">
                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="title_ar">
                                {__('dashboard.banners.title_ar')}
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
                            <label htmlFor="title_en">{__('dashboard.banners.title_en')}</label>
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

                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="subtitle_ar">{__('dashboard.banners.subtitle_ar')}</label>
                            <input
                                id="subtitle_ar"
                                name="subtitle_ar"
                                type="text"
                                className={`form-control ${isInvalid('subtitle_ar') ? 'is-invalid' : ''}`}
                                value={data.subtitle_ar}
                                onChange={(e) => setData('subtitle_ar', e.target.value)}
                                dir="rtl"
                            />
                            <FieldError error={anyErrors.subtitle_ar} />
                        </div>
                    </div>

                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="subtitle_en">{__('dashboard.banners.subtitle_en')}</label>
                            <input
                                id="subtitle_en"
                                name="subtitle_en"
                                type="text"
                                className={`form-control ${isInvalid('subtitle_en') ? 'is-invalid' : ''}`}
                                value={data.subtitle_en}
                                onChange={(e) => setData('subtitle_en', e.target.value)}
                                dir="ltr"
                            />
                            <FieldError error={anyErrors.subtitle_en} />
                        </div>
                    </div>

                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="cta_label_ar">{__('dashboard.banners.cta_label_ar')}</label>
                            <input
                                id="cta_label_ar"
                                name="cta_label_ar"
                                type="text"
                                className={`form-control ${isInvalid('cta_label_ar') ? 'is-invalid' : ''}`}
                                value={data.cta_label_ar}
                                onChange={(e) => setData('cta_label_ar', e.target.value)}
                                dir="rtl"
                            />
                            <FieldError error={anyErrors.cta_label_ar} />
                        </div>
                    </div>

                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="cta_label_en">{__('dashboard.banners.cta_label_en')}</label>
                            <input
                                id="cta_label_en"
                                name="cta_label_en"
                                type="text"
                                className={`form-control ${isInvalid('cta_label_en') ? 'is-invalid' : ''}`}
                                value={data.cta_label_en}
                                onChange={(e) => setData('cta_label_en', e.target.value)}
                                dir="ltr"
                            />
                            <FieldError error={anyErrors.cta_label_en} />
                        </div>
                    </div>

                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="image">{__('dashboard.banners.image')}</label>
                            {imageUrl ? (
                                <div className="mb-2">
                                    <img src={imageUrl} alt="" style={{ maxHeight: 120, maxWidth: '100%' }} />
                                </div>
                            ) : null}
                            <input
                                id="image"
                                name="image"
                                type="file"
                                accept="image/*"
                                className={`form-control-file ${isInvalid('image') ? 'is-invalid' : ''}`}
                                onChange={(e) => setData('image', e.target.files?.[0] ?? null)}
                            />
                            <FieldError error={anyErrors.image} />
                        </div>
                    </div>

                    <div className="col-12 col-lg-6">
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

                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="link_type">
                                {__('dashboard.banners.link_type')}
                                <RequiredMark />
                            </label>
                            <select
                                id="link_type"
                                name="link_type"
                                className={`custom-select ${isInvalid('link_type') ? 'is-invalid' : ''}`}
                                value={data.link_type}
                                onChange={(e) => {
                                    setData('link_type', e.target.value);
                                    touch('link_type');
                                }}
                            >
                                {linkTypes.map((type) => (
                                    <option key={type} value={type}>
                                        {__(`dashboard.banners.link_types.${type}`)}
                                    </option>
                                ))}
                            </select>
                            <FieldError error={fieldError('link_type')} />
                        </div>
                    </div>

                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="link_value">{__('dashboard.banners.link_value')}</label>
                            <input
                                id="link_value"
                                name="link_value"
                                type="text"
                                className={`form-control ${isInvalid('link_value') ? 'is-invalid' : ''}`}
                                value={data.link_value}
                                onChange={(e) => setData('link_value', e.target.value)}
                                dir="ltr"
                            />
                            <FieldError error={anyErrors.link_value} />
                        </div>
                    </div>

                    <div className="col-12 col-md-6">
                        <div className="form-group">
                            <label htmlFor="starts_at">{__('dashboard.banners.starts_at')}</label>
                            <input
                                id="starts_at"
                                name="starts_at"
                                type="datetime-local"
                                className={`form-control ${isInvalid('starts_at') ? 'is-invalid' : ''}`}
                                value={data.starts_at}
                                onChange={(e) => setData('starts_at', e.target.value)}
                                dir="ltr"
                            />
                            <FieldError error={anyErrors.starts_at} />
                        </div>
                    </div>

                    <div className="col-12 col-md-6">
                        <div className="form-group">
                            <label htmlFor="ends_at">{__('dashboard.banners.ends_at')}</label>
                            <input
                                id="ends_at"
                                name="ends_at"
                                type="datetime-local"
                                className={`form-control ${isInvalid('ends_at') ? 'is-invalid' : ''}`}
                                value={data.ends_at}
                                onChange={(e) => setData('ends_at', e.target.value)}
                                dir="ltr"
                            />
                            <FieldError error={anyErrors.ends_at} />
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
                                    {__('dashboard.banners.active_label')}
                                </label>
                            </div>
                            {anyErrors.is_active ? <div className="text-danger small mt-1">{anyErrors.is_active}</div> : null}
                        </div>
                    </div>
                </div>

                <div className="d-flex justify-content-end flex-wrap mt-3">
                    <Link href={route('platform.banners.index')} className="btn btn-outline-secondary px-4">
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
