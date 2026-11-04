import BannerForm, { type BannerFormData } from '@/Pages/Platform/Banners/Partials/BannerForm';
import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function PlatformBannersCreate({ linkTypes }: { linkTypes: string[] }) {
    const form = useForm<BannerFormData>({
        title_ar: '',
        title_en: '',
        subtitle_ar: '',
        subtitle_en: '',
        cta_label_ar: '',
        cta_label_en: '',
        image: null,
        link_type: 'none',
        link_value: '',
        sort_order: 0,
        is_active: true,
        starts_at: '',
        ends_at: '',
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

    const clearClientRequiredKey = (key: keyof BannerFormData) => {
        setClientRequiredErrors((prev) => {
            const next = { ...prev };
            delete next[String(key)];
            return next;
        });
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!String(form.data.title_ar ?? '').trim()) {
            setClientRequiredErrors({ title_ar: true });
            focusField('title_ar');
            return;
        }

        setClientRequiredErrors({});
        form.post(route('platform.banners.store'), {
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
            breadcrumbKey="dashboard.nav.banners"
            header={<h1 className="m-0 font-weight-bold text-dark">{__('dashboard.banners.new_title')}</h1>}
        >
            <Head title={__('dashboard.banners.new_title')} />

            <div className="mb-3">
                <Link href={route('platform.banners.index')} className="btn btn-outline-secondary btn-sm">
                    <i className="fas fa-arrow-left mr-1" />
                    {__('dashboard.actions.back')}
                </Link>
            </div>

            <form onSubmit={submit} noValidate>
                <BannerForm
                    form={form}
                    linkTypes={linkTypes}
                    clientRequiredErrors={clientRequiredErrors}
                    clearClientRequiredKey={clearClientRequiredKey}
                />
            </form>
        </PlatformAdminLayout>
    );
}
