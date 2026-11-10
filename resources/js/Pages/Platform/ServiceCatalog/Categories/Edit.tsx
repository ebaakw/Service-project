import ServiceCategoryForm, {
    type ParentOption,
    type ServiceCategoryFormData,
} from '@/Pages/Platform/ServiceCatalog/Categories/Partials/ServiceCategoryForm';
import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { firstMultipartErrorKey, submitMultipartUpdate } from '@/Utility/submitMultipartUpdate';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

type Category = {
    id: number;
    parent_id: number | null;
    name_ar: string;
    name_en: string | null;
    slug: string;
    description_ar: string | null;
    description_en: string | null;
    sort_order: number;
    is_active: boolean;
    icon_url: string | null;
};

export default function PlatformServiceCategoriesEdit({
    category,
    parentOptions = [],
}: {
    category: Category;
    parentOptions?: ParentOption[];
}) {
    const form = useForm<ServiceCategoryFormData>({
        name_ar: category.name_ar,
        name_en: category.name_en ?? '',
        slug: category.slug,
        parent_id: category.parent_id ?? '',
        icon: null,
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
        submitMultipartUpdate(form, route('platform.service-categories.update', category.id), {
            preserveState: true,
            preserveScroll: true,
            onError: (errs: Record<string, unknown>) => {
                const focusKey = firstMultipartErrorKey(errs, ['icon']);
                if (focusKey) focusField(focusKey);
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
                    iconUrl={category.icon_url}
                    parentOptions={parentOptions}
                    clientRequiredErrors={clientRequiredErrors}
                    clearClientRequiredKey={clearClientRequiredKey}
                />
            </form>
        </PlatformAdminLayout>
    );
}
