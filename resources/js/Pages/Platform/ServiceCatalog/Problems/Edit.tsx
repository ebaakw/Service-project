import ServiceProblemForm, { type ServiceProblemFormData } from '@/Pages/Platform/ServiceCatalog/Problems/ServiceProblemForm';
import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, useForm } from '@inertiajs/react';

type Category = { id: number; name_ar: string };
type Problem = {
    id: number;
    title_ar: string;
    title_en: string | null;
    description_ar: string | null;
    description_en: string | null;
    is_other: boolean;
    sort_order: number;
    is_active: boolean;
};

export default function PlatformServiceProblemsEdit({
    category,
    problem,
}: {
    category: Category;
    problem: Problem;
}) {
    const form = useForm<ServiceProblemFormData>({
        title_ar: problem.title_ar,
        title_en: problem.title_en ?? '',
        description_ar: problem.description_ar ?? '',
        description_en: problem.description_en ?? '',
        is_other: problem.is_other,
        sort_order: problem.sort_order,
        is_active: problem.is_active,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.put(route('platform.service-categories.problems.update', [category.id, problem.id]), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.global_service_catalog"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('dashboard.service_problems.edit_title')}</h4>}
        >
            <Head title={__('dashboard.service_problems.edit_title')} />

            <div className="mb-3">
                <Link href={route('platform.service-categories.problems.index', category.id)} className="btn btn-outline-secondary btn-sm">
                    <i className="fas fa-arrow-left mr-1" />
                    {__('dashboard.actions.back')}
                </Link>
            </div>

            <div className="card card-outline card-info">
                <div className="card-body py-2">
                    <span className="text-muted">{__('dashboard.service_problems.category_label')}</span>
                    <strong className="mx-1">{category.name_ar}</strong>
                </div>
            </div>

            <form onSubmit={submit} noValidate>
                <ServiceProblemForm categoryId={category.id} form={form} />
            </form>
        </PlatformAdminLayout>
    );
}
