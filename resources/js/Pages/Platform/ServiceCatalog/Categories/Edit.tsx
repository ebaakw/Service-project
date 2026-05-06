import ServiceCategoryForm, { type ServiceCategoryFormData } from '@/Pages/Platform/ServiceCatalog/Categories/Partials/ServiceCategoryForm';
import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

type Category = {
    id: number;
    name_ar: string;
    name_en: string | null;
    slug: string;
    icon_path: string | null;
    description_ar: string | null;
    description_en: string | null;
    sort_order: number;
    is_active: boolean;
};

export default function PlatformServiceCategoriesEdit({ category }: { category: Category }) {
    const form = useForm<ServiceCategoryFormData>({
        name_ar: category.name_ar,
        name_en: category.name_en ?? '',
        slug: category.slug,
        icon_path: category.icon_path ?? '',
        description_ar: category.description_ar ?? '',
        description_en: category.description_en ?? '',
        sort_order: category.sort_order,
        is_active: category.is_active,
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
        form.put(route('platform.service-categories.update', category.id), {
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
            header={<h1 className="m-0 font-weight-bold text-dark">{__('dashboard.service_categories.edit_title')}</h1>}
        >
            <Head title={__('dashboard.service_categories.edit_title')} />

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
