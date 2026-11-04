import MarketingLayout from '@/Layouts/MarketingLayout';
import { PageProps } from '@/types';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, usePage } from '@inertiajs/react';

type FeatureItem = {
    title?: string;
    text?: string;
};

export default function Features() {
    const { t } = usePage<PageProps>().props;
    const items =
        ((t as { website?: { features?: { items?: FeatureItem[] } } } | undefined)
            ?.website?.features?.items) ?? [];

    return (
        <MarketingLayout active="features">
            <Head title={__('website.features.title')} />

            <section className="khidmati-page-hero">
                <div className="khidmati-page-hero__inner">
                    <h1>{__('website.features.heading')}</h1>
                    <p>{__('website.features.lead')}</p>
                </div>
            </section>

            <section className="khidmati-section">
                <div className="khidmati-feature-list">
                    {items.map((item, index) => (
                        <article key={index} className="khidmati-feature-item">
                            <h3>{item.title}</h3>
                            <p>{item.text}</p>
                        </article>
                    ))}
                </div>
            </section>

            <section className="khidmati-cta-band">
                <div className="khidmati-cta-band__inner">
                    <div>
                        <h2>{__('website.features.cta_title')}</h2>
                    </div>
                    <div className="khidmati-hero__cta">
                        <Link href={route('login')} className="khidmati-btn khidmati-btn--primary">
                            {__('website.features.cta_login')}
                        </Link>
                        <Link
                            href={route('website.contact')}
                            className="khidmati-btn khidmati-btn--ghost"
                        >
                            {__('website.features.cta_contact')}
                        </Link>
                    </div>
                </div>
            </section>
        </MarketingLayout>
    );
}
