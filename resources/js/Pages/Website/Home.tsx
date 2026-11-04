import MarketingLayout from '@/Layouts/MarketingLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link } from '@inertiajs/react';

export default function Home() {
    return (
        <MarketingLayout active="home">
            <Head title={__('website.home.title')} />

            <section className="khidmati-hero">
                <div className="khidmati-hero__inner">
                    <h1 className="khidmati-hero__brand">{__('website.home.hero_brand')}</h1>
                    <p className="khidmati-hero__headline">
                        {__('website.home.hero_headline')}
                    </p>
                    <p className="khidmati-hero__support">
                        {__('website.home.hero_support')}
                    </p>
                    <div className="khidmati-hero__cta">
                        <Link href={route('login')} className="khidmati-btn khidmati-btn--primary">
                            {__('website.home.cta_login')}
                        </Link>
                        <Link
                            href={route('website.features')}
                            className="khidmati-btn khidmati-btn--ghost"
                        >
                            {__('website.home.cta_features')}
                        </Link>
                    </div>
                </div>
            </section>

            <section className="khidmati-section">
                <h2 className="khidmati-section__title">
                    {__('website.home.overview_title')}
                </h2>
                <p className="khidmati-section__lead">
                    {__('website.home.overview_text')}
                </p>

                <div className="khidmati-audience-grid">
                    <div className="khidmati-audience-item">
                        <h3>{__('website.home.audience_companies_title')}</h3>
                        <p>{__('website.home.audience_companies_text')}</p>
                    </div>
                    <div className="khidmati-audience-item">
                        <h3>{__('website.home.audience_technicians_title')}</h3>
                        <p>{__('website.home.audience_technicians_text')}</p>
                    </div>
                    <div className="khidmati-audience-item">
                        <h3>{__('website.home.audience_customers_title')}</h3>
                        <p>{__('website.home.audience_customers_text')}</p>
                    </div>
                </div>
            </section>

            <section className="khidmati-cta-band">
                <div className="khidmati-cta-band__inner">
                    <div>
                        <h2>{__('website.home.cta_band_title')}</h2>
                        <p>{__('website.home.cta_band_text')}</p>
                    </div>
                    <Link
                        href={route('website.contact')}
                        className="khidmati-btn khidmati-btn--primary"
                    >
                        {__('website.home.cta_band_contact')}
                    </Link>
                </div>
            </section>
        </MarketingLayout>
    );
}
