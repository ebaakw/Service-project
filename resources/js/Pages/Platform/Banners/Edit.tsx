import BannerForm, { type BannerFormData } from '@/Pages/Platform/Banners/Partials/BannerForm';
import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { firstMultipartErrorKey, submitMultipartUpdate } from '@/Utility/submitMultipartUpdate';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

type Banner = {
    id: number;
    title_ar: string;
    title_en: string | null;
    subtitle_ar: string | null;
    subtitle_en: string | null;
    cta_label_ar: string | null;
    cta_label_en: string | null;
    link_type: string;
    link_value: string | null;
    sort_order: number;
    is_active: boolean;
    image_url: string | null;
    starts_at: string | null;
    ends_at: string | null;
};

export default function PlatformBannersEdit({ banner, linkTypes }: { banner: Banner; linkTypes: string[] }) {
    const form = useForm<BannerFormData>({
        title_ar: banner.title_ar,
        title_en: banner.title_en ?? '',
        subtitle_ar: banner.subtitle_ar ?? '',
        subtitle_en: banner.subtitle_en ?? '',
        cta_label_ar: banner.cta_label_ar ?? '',
        cta_label_en: banner.cta_label_en ?? '',
        image: null,
        link_type: banner.link_type || 'none',
        link_value: banner.link_value ?? '',
        sort_order: banner.sort_order,
        is_active: banner.is_active,
        starts_at: banner.starts_at ?? '',
        ends_at: banner.ends_at ?? '',
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
        submitMultipartUpdate(form, route('platform.banners.update', banner.id), {
            preserveState: true,
            preserveScroll: true,
            onError: (errs: Record<string, unknown>) => {
                const focusKey = firstMultipartErrorKey(errs, ['image']);
                if (focusKey) focusField(focusKey);
            },
        });
    };

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.banners"
            header={<h1 className="m-0 font-weight-bold text-dark">{__('dashboard.banners.edit_title')}</h1>}
        >
            <Head title={__('dashboard.banners.edit_title')} />

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
                    imageUrl={banner.image_url}
                    clientRequiredErrors={clientRequiredErrors}
                    clearClientRequiredKey={clearClientRequiredKey}
                />
            </form>
        </PlatformAdminLayout>
    );
}
