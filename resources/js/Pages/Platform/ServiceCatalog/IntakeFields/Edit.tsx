import ServiceIntakeFieldForm, {
    type OptionRow,
    type ProblemOption,
    type ServiceIntakeFieldFormData,
} from '@/Pages/Platform/ServiceCatalog/IntakeFields/ServiceIntakeFieldForm';
import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, useForm } from '@inertiajs/react';

type Category = { id: number; name_ar: string };
type Field = {
    id: number;
    service_problem_id: number | null;
    label_ar: string;
    label_en: string | null;
    field_type: string;
    options: OptionRow[] | null;
    placeholder_ar: string | null;
    placeholder_en: string | null;
    is_required: boolean;
    sort_order: number;
    is_active: boolean;
};

export default function PlatformIntakeFieldsEdit({
    category,
    problems,
    field,
}: {
    category: Category;
    problems: ProblemOption[];
    field: Field;
}) {
    const form = useForm<ServiceIntakeFieldFormData>({
        service_problem_id: (field.service_problem_id ?? '') as string | number,
        label_ar: field.label_ar,
        label_en: field.label_en ?? '',
        field_type: field.field_type,
        options: (field.options ?? []) as OptionRow[],
        placeholder_ar: field.placeholder_ar ?? '',
        placeholder_en: field.placeholder_en ?? '',
        is_required: field.is_required,
        sort_order: field.sort_order,
        is_active: field.is_active,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((raw) => {
            const type = String(raw.field_type);
            const wantsOptions = type === 'select' || type === 'multi_select';

            return {
                ...raw,
                service_problem_id: raw.service_problem_id === '' ? null : Number(raw.service_problem_id),
                options: wantsOptions ? raw.options : [],
            };
        });
        form.put(route('platform.service-categories.intake-fields.update', [category.id, field.id]), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.global_service_catalog"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('dashboard.service_intake_fields.edit_title')}</h4>}
        >
            <Head title={__('dashboard.service_intake_fields.edit_title')} />

            <div className="mb-3">
                <Link href={route('platform.service-categories.intake-fields.index', category.id)} className="btn btn-outline-secondary btn-sm">
                    <i className="fas fa-arrow-left mr-1" />
                    {__('dashboard.actions.back')}
                </Link>
            </div>

            <div className="card card-outline card-info">
                <div className="card-body py-2">
                    <span className="text-muted">{__('dashboard.service_intake_fields.category_label')}</span>
                    <strong className="mx-1">{category.name_ar}</strong>
                </div>
            </div>

            <form onSubmit={submit} noValidate>
                <ServiceIntakeFieldForm categoryId={category.id} problems={problems} form={form} />
            </form>
        </PlatformAdminLayout>
    );
}
