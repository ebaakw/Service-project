import '@/Components/Core/css/switch.css';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { InertiaFormProps } from '@inertiajs/react';
import { Link } from '@inertiajs/react';

export type ServiceProblemFormData = {
    title_ar: string;
    title_en: string;
    description_ar: string;
    description_en: string;
    icon: File | null;
    is_other: boolean;
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

export default function ServiceProblemForm({
    categoryId,
    form,
    iconUrl,
}: {
    categoryId: number;
    form: InertiaFormProps<ServiceProblemFormData>;
    iconUrl?: string | null;
}) {
    const { data, setData, errors, processing } = form;
    const anyErrors = errors as Record<string, string | undefined>;
    const isInvalid = (key: keyof ServiceProblemFormData) => Boolean(anyErrors[String(key)]);

    return (
        <div className="card card-outline card-primary">
            <div className="card-body">
                <div className="row">
                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="title_ar">
                                {__('dashboard.forms.title_ar')}
                                <RequiredMark />
                            </label>
                            <input
                                id="title_ar"
                                name="title_ar"
                                type="text"
                                className={`form-control ${isInvalid('title_ar') ? 'is-invalid' : ''}`}
                                value={data.title_ar}
                                onChange={(e) => setData('title_ar', e.target.value)}
                                dir="rtl"
                            />
                            <FieldError error={anyErrors.title_ar} />
                        </div>
                    </div>

                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="title_en">{__('dashboard.forms.title_en')}</label>
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

                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="icon">{__('dashboard.service_problems.icon')}</label>
                            {iconUrl ? (
                                <div className="mb-2">
                                    <img src={iconUrl} alt="" style={{ maxHeight: 120, maxWidth: '100%' }} />
                                </div>
                            ) : null}
                            <input
                                id="icon"
                                name="icon"
                                type="file"
                                accept="image/*"
                                className={`form-control-file ${isInvalid('icon') ? 'is-invalid' : ''}`}
                                onChange={(e) => setData('icon', e.target.files?.[0] ?? null)}
                            />
                            <FieldError error={anyErrors.icon} />
                        </div>
                    </div>

                    <div className="col-12 col-md-4">
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

                    <div className="col-12 col-md-8 d-flex align-items-end">
                        <div className="form-group w-100">
                            <div className="d-flex flex-wrap align-items-center">
                                <div className="custom-control custom-switch custom-switch-md mr-4">
                                    <input
                                        id="is_other"
                                        name="is_other"
                                        type="checkbox"
                                        className="custom-control-input"
                                        checked={data.is_other}
                                        onChange={(e) => setData('is_other', e.target.checked)}
                                    />
                                    <label htmlFor="is_other" className="custom-control-label pt-1 pl-3">
                                        {__('dashboard.service_problems.other_label')}
                                    </label>
                                </div>

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
                            </div>
                            {anyErrors.is_other ? <div className="text-danger small mt-1">{anyErrors.is_other}</div> : null}
                            {anyErrors.is_active ? <div className="text-danger small mt-1">{anyErrors.is_active}</div> : null}
                        </div>
                    </div>
                </div>

                <div className="d-flex justify-content-end flex-wrap mt-3">
                    <Link href={route('platform.service-categories.problems.index', categoryId)} className="btn btn-outline-secondary px-4">
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
