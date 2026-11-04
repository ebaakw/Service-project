import ContentPageForm, { type ContentPageFormData } from '@/Pages/Platform/ContentPages/Partials/ContentPageForm';
import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function PlatformContentPagesCreate() {
    const form = useForm<ContentPageFormData>({
        slug: '',
        title_ar: '',
        title_en: '',
        body_ar: '',
        body_en: '',
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
        form.post(route('platform.content-pages.store'), {
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
            header={<h1 className="m-0 font-weight-bold text-dark">{__('dashboard.content_pages.new_title')}</h1>}
        >
            <Head title={__('dashboard.content_pages.new_title')} />

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
