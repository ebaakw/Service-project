import ServiceCategoryForm, { type ServiceCategoryFormData } from '@/Pages/Platform/ServiceCatalog/Categories/Partials/ServiceCategoryForm';
import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function PlatformServiceCategoriesCreate() {
    const form = useForm<ServiceCategoryFormData>({
        name_ar: '',
        name_en: '',
        slug: '',
        icon_path: '',
        description_ar: '',
        description_en: '',
        sort_order: 0,
        is_active: true,
    });

    const [clientRequiredErrors, setClientRequiredErrors] = useState<Record<string, boolean>>({});

    const focusField = (name: string) => {
        const el = document.querySelector<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>(
            `[name="${CSS.escape(name)}"]`,
        );
        if (!el) return;
        el.classList.add('is-invalid');
        el.scrollIntoView({ block: 'center', behavior: 'smooth' });
        el.focus();
    };

    const clearClientRequiredKey = (key: keyof ServiceCategoryFormData) => {
        setClientRequiredErrors((prev) => {
            const next = { ...prev };
            delete next[String(key)];
            return next;
        });
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const required: Array<keyof ServiceCategoryFormData> = ['name_ar', 'name_en', 'slug'];
        const missing = required.find((k) => String((form.data as any)[k] ?? '').trim() === '');

        if (missing) {
            setClientRequiredErrors({ [String(missing)]: true });
            focusField(String(missing));
            return;
        }

        setClientRequiredErrors({});
        form.post(route('platform.service-categories.store'), {
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
            breadcrumbKey="dashboard.nav.global_service_catalog"
            header={<h1 className="m-0 font-weight-bold text-dark">{__('dashboard.service_categories.new_title')}</h1>}
        >
            <Head title={__('dashboard.service_categories.new_title')} />

            <div className="mb-3">
                <Link href={route('platform.service-categories.index')} className="btn btn-outline-secondary btn-sm">
                    <i className="fas fa-arrow-left mr-1" />
                    {__('dashboard.actions.back')}
                </Link>
            </div>

            <form onSubmit={submit} noValidate>
                <ServiceCategoryForm
                    form={form}
                    clientRequiredErrors={clientRequiredErrors}
                    clearClientRequiredKey={clearClientRequiredKey}
                />
            </form>
        </PlatformAdminLayout>
    );
}
