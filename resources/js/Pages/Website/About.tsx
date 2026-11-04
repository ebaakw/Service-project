import MarketingLayout from '@/Layouts/MarketingLayout';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head } from '@inertiajs/react';

export default function About() {
    return (
        <MarketingLayout active="about">
            <Head title={__('website.about.title')} />

            <section className="khidmati-page-hero">
                <div className="khidmati-page-hero__inner">
                    <h1>{__('website.about.heading')}</h1>
                    <p>{__('website.about.lead')}</p>
                </div>
            </section>

            <section className="khidmati-section">
                <div className="khidmati-prose">
                    <div>
                        <h2>{__('website.about.story_title')}</h2>
                        <p>{__('website.about.story_text')}</p>
                    </div>

                    <div>
                        <h2>{__('website.about.audience_title')}</h2>
                        <ul>
                            <li>{__('website.about.audience_platform')}</li>
                            <li>{__('website.about.audience_company')}</li>
                            <li>{__('website.about.audience_field')}</li>
                        </ul>
                    </div>

                    <div>
                        <h2>{__('website.about.value_title')}</h2>
                        <ul>
                            <li>{__('website.about.value_1')}</li>
                            <li>{__('website.about.value_2')}</li>
                            <li>{__('website.about.value_3')}</li>
                            <li>{__('website.about.value_4')}</li>
                        </ul>
                    </div>
                </div>
            </section>
        </MarketingLayout>
    );
}
