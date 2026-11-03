import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import CompanyForm from '@/Pages/Platform/Companies/Partials/CompanyForm';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

type Option = { value: string; label: string };

type DocumentItem = {
    id: number;
    url: string;
    sort_order: number;
    file_name: string | null;
    mime_type: string | null;
    size: number | null;
};

type Company = {
    id: number;
    name_ar: string;
    name_en: string | null;
    slug: string | null;
    logo_url: string | null;
    phone: string | null;
    email: string | null;
    contacts: Array<{ name?: string | null; phone?: string | null; position?: string | null }>;
    address: string | null;
    working_hours: string | null;
    iban: string | null;
    bank_name: string | null;
    documents: {
        license: DocumentItem[];
        signature_authorization: DocumentItem[];
        additional_documents: DocumentItem[];
    };
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

type PrimaryUser = {
    id: number;
    name: string;
    name_ar: string | null;
    name_en: string | null;
    email: string;
    phone: string | null;
    status: string;
} | null;

export default function PlatformCompaniesEdit({
    company,
    primaryUser,
    enums,
    userStatuses,
}: {
    company: Company;
    primaryUser: PrimaryUser;
    enums: { company_status: Option[]; subscription_status: Option[] };
    userStatuses: Option[];
}) {
    const initialContacts =
        Array.isArray(company.contacts) && company.contacts.length > 0
            ? company.contacts.map((c) => ({
                  name: c.name ?? '',
                  phone: c.phone ?? '',
                  position: c.position ?? '',
              }))
            : [];

    const form = useForm(`platform.companies.edit.${company.id}`, {
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
        contacts: initialContacts,
        address: company.address ?? '',
        working_hours: company.working_hours ?? '',
        iban: company.iban ?? '',
        bank_name: company.bank_name ?? '',
        license_files: [] as File[],
        signature_authorization_files: [] as File[],
        additional_documents_files: [] as File[],
        remove_document_ids: [] as number[],

        owner_user_id: primaryUser?.id ?? '',
        create_owner: false,
        owner_name_ar: primaryUser?.name_ar ?? '',
        owner_name_en: primaryUser?.name_en ?? '',
        owner_email: primaryUser?.email ?? '',
        owner_phone: primaryUser?.phone ?? '',
        owner_status: primaryUser?.status ?? 'active',
        owner_password: '',
        owner_password_confirmation: '',
    });

    const [clientRequiredErrors, setClientRequiredErrors] = useState<Record<string, boolean>>({});

    const focusField = (name: string) => {
        const el = document.querySelector<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>(`[name="${CSS.escape(name)}"]`);
        if (!el) return;
        el.classList.add('is-invalid');
        el.scrollIntoView({ block: 'center', behavior: 'smooth' });
        el.focus();
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const required: Array<keyof typeof form.data> = ['name_ar', 'status', 'subscription_status'];

        const hasPrimaryUser = Boolean(primaryUser && primaryUser.id);
        if (!hasPrimaryUser && form.data.create_owner) {
            required.push('owner_name_ar', 'owner_email', 'owner_password', 'owner_password_confirmation');
        }
        const missing = required.find((k) => String((form.data as any)[k] ?? '').trim() === '');

        if (missing) {
            setClientRequiredErrors({ [String(missing)]: true });
            focusField(String(missing));
            return;
        }

        setClientRequiredErrors({});
        form.put(route('platform.companies.update', company.id), {
            forceFormData: true,
            preserveState: true,
            preserveScroll: true,
            onError: (errs) => {
                const firstKey = Object.keys(errs ?? {})[0];
                if (firstKey) focusField(firstKey);
            },
        });
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
                <CompanyForm
                    form={form as any}
                    mode="edit"
                    enums={enums}
                    logoUrl={company.logo_url}
                    clientRequiredErrors={clientRequiredErrors}
                    primaryUser={primaryUser}
                    userStatuses={userStatuses}
                    documents={company.documents}
                />
            </form>
        </PlatformAdminLayout>
    );
}
