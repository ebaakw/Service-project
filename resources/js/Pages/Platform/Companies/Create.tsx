import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import CompanyForm from '@/Pages/Platform/Companies/Partials/CompanyForm';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

type Option = { value: string; label: string };

export default function PlatformCompaniesCreate({
    enums,
}: {
    enums: { company_status: Option[]; subscription_status: Option[] };
}) {
    const form = useForm('platform.companies.create', {
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
        warranty_note_ar: '',
        warranty_note_en: '',
        status: 'active',
        subscription_status: 'trial',
        subscription_starts_at: '',
        subscription_ends_at: '',
        is_featured: false,
        default_inspection_fee: '',
        allow_final_invoice_without_items: false,
        contacts: [] as Array<{ name: string; phone: string; position: string }>,
        address: '',
        working_hours: '',
        iban: '',
        bank_name: '',
        license_files: [] as File[],
        signature_authorization_files: [] as File[],
        additional_documents_files: [] as File[],
        create_owner: false,
        owner_name_ar: '',
        owner_name_en: '',
        owner_email: '',
        owner_phone: '',
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

        if (form.data.create_owner) {
            required.push('owner_name_ar', 'owner_email', 'owner_password', 'owner_password_confirmation');
        }
        const missing = required.find((k) => String((form.data as any)[k] ?? '').trim() === '');

        if (missing) {
            setClientRequiredErrors({ [String(missing)]: true });
            focusField(String(missing));
            return;
        }

        setClientRequiredErrors({});
        form.post(route('platform.companies.store'), {
            forceFormData: true,
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                form.dontRemember('owner_password', 'owner_password_confirmation');
            },
            onError: (errs) => {
                const firstKey = Object.keys(errs ?? {})[0];
                if (firstKey) focusField(firstKey);
            },
        });
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
                <CompanyForm form={form as any} mode="create" enums={enums} clientRequiredErrors={clientRequiredErrors} />
            </form>
        </PlatformAdminLayout>
    );
}
