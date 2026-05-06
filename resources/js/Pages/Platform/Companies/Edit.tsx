import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import CompanyForm from '@/Pages/Platform/Companies/Partials/CompanyForm';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, useForm } from '@inertiajs/react';

type Option = { value: string; label: string };
type Company = {
    id: number;
    name_ar: string;
    name_en: string | null;
    slug: string | null;
    logo_url: string | null;
    phone: string | null;
    email: string | null;
    short_description_ar: string | null;
    short_description_en: string | null;
    description_ar: string | null;
    description_en: string | null;
    status: string;
    subscription_status: string;
    subscription_starts_at: string | null;
    subscription_ends_at: string | null;
    is_featured: boolean;
    default_inspection_fee: string | number | null;
    allow_final_invoice_without_items: boolean;
};

export default function PlatformCompaniesEdit({
    company,
    enums,
}: {
    company: Company;
    enums: { company_status: Option[]; subscription_status: Option[] };
}) {
    const form = useForm({
        name_ar: company.name_ar,
        name_en: company.name_en ?? '',
        slug: company.slug ?? '',
        logo: null as File | null,
        phone: company.phone ?? '',
        email: company.email ?? '',
        short_description_ar: company.short_description_ar ?? '',
        short_description_en: company.short_description_en ?? '',
        description_ar: company.description_ar ?? '',
        description_en: company.description_en ?? '',
        status: company.status,
        subscription_status: company.subscription_status,
        subscription_starts_at: company.subscription_starts_at ?? '',
        subscription_ends_at: company.subscription_ends_at ?? '',
        is_featured: company.is_featured,
        default_inspection_fee:
            company.default_inspection_fee === null || company.default_inspection_fee === undefined
                ? ''
                : String(company.default_inspection_fee),
        allow_final_invoice_without_items: company.allow_final_invoice_without_items,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.put(route('platform.companies.update', company.id), { forceFormData: true });
    };

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.companies"
            header={<h1 className="m-0 text-2xl font-weight-bold text-dark">{__('tenant.companies.edit')}</h1>}
        >
            <Head title={__('tenant.companies.edit')} />

            <div className="mb-3 d-flex gap-2 flex-wrap">
                <Link href={route('platform.companies.index')} className="btn btn-outline-secondary btn-sm">
                    <i className="fas fa-arrow-left mr-1" />
                    {__('dashboard.actions.back')}
                </Link>
            </div>

            <form onSubmit={submit}>
                <CompanyForm form={form as any} mode="edit" enums={enums} logoUrl={company.logo_url} />
            </form>
        </PlatformAdminLayout>
    );
}

