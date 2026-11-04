import ContentPageForm, { type ContentPageFormData } from '@/Pages/Platform/ContentPages/Partials/ContentPageForm';
import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

type Page = {
    id: number;
    slug: string;
    title_ar: string;
    title_en: string | null;
    body_ar: string | null;
    body_en: string | null;
    is_active: boolean;
};

export default function PlatformContentPagesEdit({ page }: { page: Page }) {
    const form = useForm<ContentPageFormData>({
        slug: page.slug,
        title_ar: page.title_ar,
        title_en: page.title_en ?? '',
        body_ar: page.body_ar ?? '',
        body_en: page.body_en ?? '',
        is_active: page.is_active,
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

    const clearClientRequiredKey = (key: keyof ContentPageFormData) => {
        setClientRequiredErrors((prev) => {
            const next = { ...prev };
            delete next[String(key)];
            return next;
        });
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const required: Array<keyof ContentPageFormData> = ['slug', 'title_ar'];
        const missing = required.find((k) => String((form.data as any)[k] ?? '').trim() === '');

        if (missing) {
            setClientRequiredErrors({ [String(missing)]: true });
            focusField(String(missing));
            return;
        }

        setClientRequiredErrors({});
        form.put(route('platform.content-pages.update', page.id), {
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
            breadcrumbKey="dashboard.nav.content_pages"
            header={<h1 className="m-0 font-weight-bold text-dark">{__('dashboard.content_pages.edit_title')}</h1>}
        >
            <Head title={__('dashboard.content_pages.edit_title')} />

            <div className="mb-3">
                <Link href={route('platform.content-pages.index')} className="btn btn-outline-secondary btn-sm">
                    <i className="fas fa-arrow-left mr-1" />
                    {__('dashboard.actions.back')}
                </Link>
            </div>

            <form onSubmit={submit} noValidate>
                <ContentPageForm
                    form={form}
                    clientRequiredErrors={clientRequiredErrors}
                    clearClientRequiredKey={clearClientRequiredKey}
                />
            </form>
        </PlatformAdminLayout>
    );
}
