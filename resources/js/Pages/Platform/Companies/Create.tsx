import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import CompanyForm from '@/Pages/Platform/Companies/Partials/CompanyForm';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, useForm } from '@inertiajs/react';

type Option = { value: string; label: string };

export default function PlatformCompaniesCreate({
    enums,
}: {
    enums: { company_status: Option[]; subscription_status: Option[] };
}) {
    const form = useForm({
        name_ar: '',
        name_en: '',
        slug: '',
        logo: null as File | null,
        phone: '',
        email: '',
        short_description_ar: '',
        short_description_en: '',
        description_ar: '',
        description_en: '',
        status: 'active',
        subscription_status: 'trial',
        subscription_starts_at: '',
        subscription_ends_at: '',
        is_featured: false,
        default_inspection_fee: '',
        allow_final_invoice_without_items: false,
        owner_name_ar: '',
        owner_name_en: '',
        owner_email: '',
        owner_phone: '',
        owner_password: '',
        owner_password_confirmation: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(route('platform.companies.store'), { forceFormData: true });
    };

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.companies"
            header={<h1 className="m-0 font-weight-bold text-dark">{__('tenant.companies.create')}</h1>}
        >
            <Head title={__('tenant.companies.create')} />

            <div className="mb-3">
                <Link href={route('platform.companies.index')} className="btn btn-outline-secondary btn-sm">
                    <i className="fas fa-arrow-left mr-1" />
                    {__('dashboard.actions.back')}
                </Link>
            </div>

            <form onSubmit={submit}>
                <CompanyForm form={form as any} mode="create" enums={enums} />
            </form>
        </PlatformAdminLayout>
    );
}

