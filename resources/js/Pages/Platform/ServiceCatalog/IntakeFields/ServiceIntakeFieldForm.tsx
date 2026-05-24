import '@/Components/Core/css/switch.css';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { InertiaFormProps } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { useMemo } from 'react';

export type ProblemOption = { id: number; title_ar: string; title_en: string | null };
export type OptionRow = { value: string; label_ar: string; label_en: string };

export const FIELD_TYPES = [
    'text',
    'textarea',
    'number',
    'select',
    'multi_select',
    'boolean',
    'date',
    'image',
] as const;

export type ServiceIntakeFieldFormData = {
    service_problem_id: string | number;
    label_ar: string;
    label_en: string;
    field_type: (typeof FIELD_TYPES)[number] | string;
    options: OptionRow[];
    placeholder_ar: string;
    placeholder_en: string;
    is_required: boolean;
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

export default function ServiceIntakeFieldForm({
    categoryId,
    problems,
    form,
}: {
    categoryId: number;
    problems: ProblemOption[];
    form: InertiaFormProps<ServiceIntakeFieldFormData>;
}) {
    const { data, setData, errors, processing } = form;
    const anyErrors = errors as Record<string, string | undefined>;
    const isInvalid = (key: keyof ServiceIntakeFieldFormData) => Boolean(anyErrors[String(key)]);

    const needsOptions = useMemo(
        () => data.field_type === 'select' || data.field_type === 'multi_select',
        [data.field_type],
    );

    const addOption = () => {
        setData('options', [...data.options, { value: '', label_ar: '', label_en: '' }]);
    };

    const updateOption = (index: number, key: keyof OptionRow, value: string) => {
        setData(
            'options',
            data.options.map((option, optionIndex) => (optionIndex === index ? { ...option, [key]: value } : option)),
        );
    };

    const removeOption = (index: number) => {
        setData(
            'options',
            data.options.filter((_, optionIndex) => optionIndex !== index),
        );
    };

    return (
        <div className="card card-outline card-primary">
            <div className="card-body">
                <div className="row">
                    <div className="col-12">
                        <div className="form-group">
                            <label htmlFor="service_problem_id">{__('dashboard.service_intake_fields.scope_label')}</label>
                            <select
                                id="service_problem_id"
                                name="service_problem_id"
                                className={`custom-select ${isInvalid('service_problem_id') ? 'is-invalid' : ''}`}
                                value={data.service_problem_id}
                                onChange={(e) =>
                                    setData('service_problem_id', e.target.value === '' ? '' : Number(e.target.value))
                                }
                            >
                                <option value="">{__('dashboard.service_intake_fields.category_only')}</option>
                                {problems.map((problem) => (
                                    <option key={problem.id} value={problem.id}>
                                        {problem.title_ar}
                                    </option>
                                ))}
                            </select>
                            <FieldError error={anyErrors.service_problem_id} />
                        </div>
                    </div>

                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="label_ar">
                                {__('dashboard.forms.label_ar')}
                                <RequiredMark />
                            </label>
                            <input
                                id="label_ar"
                                name="label_ar"
                                type="text"
                                className={`form-control ${isInvalid('label_ar') ? 'is-invalid' : ''}`}
                                value={data.label_ar}
                                onChange={(e) => setData('label_ar', e.target.value)}
                                dir="rtl"
                            />
                            <FieldError error={anyErrors.label_ar} />
                        </div>
                    </div>

                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="label_en">{__('dashboard.forms.label_en')}</label>
                            <input
                                id="label_en"
                                name="label_en"
                                type="text"
                                className={`form-control ${isInvalid('label_en') ? 'is-invalid' : ''}`}
                                value={data.label_en}
                                onChange={(e) => setData('label_en', e.target.value)}
                                dir="ltr"
                            />
                            <FieldError error={anyErrors.label_en} />
                        </div>
                    </div>

                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="field_type">{__('dashboard.forms.field_type')}</label>
                            <select
                                id="field_type"
                                name="field_type"
                                className={`custom-select ${isInvalid('field_type') ? 'is-invalid' : ''}`}
                                value={data.field_type}
                                onChange={(e) => setData('field_type', e.target.value)}
                            >
                                {FIELD_TYPES.map((fieldType) => (
                                    <option key={fieldType} value={fieldType}>
                                        {__('dashboard.service_intake_fields.field_types.' + fieldType)}
                                    </option>
                                ))}
                            </select>
                            <FieldError error={anyErrors.field_type} />
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

                    {needsOptions ? (
                        <div className="col-12">
                            <div className="card card-outline card-info">
                                <div className="card-header">
                                    <h3 className="card-title">{__('dashboard.service_intake_fields.options_title')}</h3>
                                    <div className="card-tools">
                                        <button type="button" className="btn btn-outline-info btn-sm" onClick={addOption}>
                                            <i className="fas fa-plus mr-1" />
                                            {__('dashboard.service_intake_fields.add_option')}
                                        </button>
                                    </div>
                                </div>
                                <div className="card-body">
                                    {data.options.map((option, index) => (
                                        <div key={index} className="row align-items-end border rounded p-2 mb-2">
                                            <div className="col-12 col-md-3">
                                                <div className="form-group mb-md-0">
                                                    <label>{__('dashboard.service_intake_fields.option_value')}</label>
                                                    <input
                                                        type="text"
                                                        className="form-control"
                                                        value={option.value}
                                                        onChange={(e) => updateOption(index, 'value', e.target.value)}
                                                        dir="ltr"
                                                    />
                                                </div>
                                            </div>
                                            <div className="col-12 col-md-4">
                                                <div className="form-group mb-md-0">
                                                    <label>{__('dashboard.service_intake_fields.option_label_ar')}</label>
                                                    <input
                                                        type="text"
                                                        className="form-control"
                                                        value={option.label_ar}
                                                        onChange={(e) => updateOption(index, 'label_ar', e.target.value)}
                                                        dir="rtl"
                                                    />
                                                </div>
                                            </div>
                                            <div className="col-12 col-md-4">
                                                <div className="form-group mb-md-0">
                                                    <label>{__('dashboard.service_intake_fields.option_label_en')}</label>
                                                    <input
                                                        type="text"
                                                        className="form-control"
                                                        value={option.label_en}
                                                        onChange={(e) => updateOption(index, 'label_en', e.target.value)}
                                                        dir="ltr"
                                                    />
                                                </div>
                                            </div>
                                            <div className="col-12 col-md-1">
                                                <button
                                                    type="button"
                                                    className="btn btn-outline-danger btn-sm"
                                                    onClick={() => removeOption(index)}
                                                    aria-label={__('dashboard.actions.remove')}
                                                >
                                                    <i className="fas fa-times" />
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                    {anyErrors.options ? <div className="text-danger small mt-1">{anyErrors.options}</div> : null}
                                </div>
                            </div>
                        </div>
                    ) : null}

                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="placeholder_ar">{__('dashboard.forms.placeholder_ar')}</label>
                            <input
                                id="placeholder_ar"
                                name="placeholder_ar"
                                type="text"
                                className={`form-control ${isInvalid('placeholder_ar') ? 'is-invalid' : ''}`}
                                value={data.placeholder_ar}
                                onChange={(e) => setData('placeholder_ar', e.target.value)}
                                dir="rtl"
                            />
                            <FieldError error={anyErrors.placeholder_ar} />
                        </div>
                    </div>

                    <div className="col-12 col-lg-6">
                        <div className="form-group">
                            <label htmlFor="placeholder_en">{__('dashboard.forms.placeholder_en')}</label>
                            <input
                                id="placeholder_en"
                                name="placeholder_en"
                                type="text"
                                className={`form-control ${isInvalid('placeholder_en') ? 'is-invalid' : ''}`}
                                value={data.placeholder_en}
                                onChange={(e) => setData('placeholder_en', e.target.value)}
                                dir="ltr"
                            />
                            <FieldError error={anyErrors.placeholder_en} />
                        </div>
                    </div>

                    <div className="col-12">
                        <div className="form-group">
                            <div className="d-flex flex-wrap align-items-center">
                                <div className="custom-control custom-switch custom-switch-md mr-4">
                                    <input
                                        id="is_required"
                                        name="is_required"
                                        type="checkbox"
                                        className="custom-control-input"
                                        checked={data.is_required}
                                        onChange={(e) => setData('is_required', e.target.checked)}
                                    />
                                    <label htmlFor="is_required" className="custom-control-label pt-1 pl-3">
                                        {__('dashboard.service_intake_fields.required_label')}
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
                            {anyErrors.is_required ? <div className="text-danger small mt-1">{anyErrors.is_required}</div> : null}
                            {anyErrors.is_active ? <div className="text-danger small mt-1">{anyErrors.is_active}</div> : null}
                        </div>
                    </div>
                </div>

                <div className="d-flex justify-content-end flex-wrap mt-3">
                    <Link href={route('platform.service-categories.intake-fields.index', categoryId)} className="btn btn-outline-secondary px-4">
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
