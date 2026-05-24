import ServiceProblemForm, { type ServiceProblemFormData } from '@/Pages/Platform/ServiceCatalog/Problems/ServiceProblemForm';
import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, useForm } from '@inertiajs/react';

type Category = { id: number; name_ar: string };

export default function PlatformServiceProblemsCreate({ category }: { category: Category }) {
    const form = useForm<ServiceProblemFormData>({
        title_ar: '',
        title_en: '',
        description_ar: '',
        description_en: '',
        is_other: false,
        sort_order: 0,
        is_active: true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(route('platform.service-categories.problems.store', category.id), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.global_service_catalog"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('dashboard.service_problems.new_title')}</h4>}
        >
            <Head title={__('dashboard.service_problems.new_title')} />

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
