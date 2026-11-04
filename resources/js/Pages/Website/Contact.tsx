import MarketingLayout from '@/Layouts/MarketingLayout';
import { PageProps } from '@/types';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function Contact() {
    const { direction, flash } = usePage<
        PageProps & {
            flash?: { type?: string | null; message?: string | null };
        }
    >().props;

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        message: '',
    });

    const align = direction === 'rtl' ? 'right' : 'left';

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(route('website.contact.submit'), {
            preserveScroll: true,
            onSuccess: () => reset('name', 'email', 'message'),
        });
    };

    return (
        <MarketingLayout active="contact">
            <Head title={__('website.contact.title')} />

            <section className="khidmati-page-hero">
                <div className="khidmati-page-hero__inner">
                    <h1>{__('website.contact.heading')}</h1>
                    <p>{__('website.contact.lead')}</p>
                </div>
            </section>

            <section className="khidmati-section">
                <div className="khidmati-contact-grid">
                    <div>
                        {flash?.type === 'success' && flash.message && (
                            <div className="khidmati-alert" role="status">
                                {flash.message}
                            </div>
                        )}

                        <form className="khidmati-form" onSubmit={submit} noValidate>
                            <label>
                                <span>
                                    {__('website.contact.name')}
                                    <span aria-hidden="true"> *</span>
                                </span>
                                <input
                                    type="text"
                                    name="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder={__('website.contact.name')}
                                    aria-label={__('website.contact.name')}
                                    style={{ textAlign: align }}
                                    required
                                />
                                {errors.name && (
                                    <span className="khidmati-form__error">{errors.name}</span>
                                )}
                            </label>

                            <label>
                                <span>
                                    {__('website.contact.email')}
                                    <span aria-hidden="true"> *</span>
                                </span>
                                <input
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder={__('website.contact.email')}
                                    aria-label={__('website.contact.email')}
                                    dir="ltr"
                                    style={{ textAlign: align }}
                                    required
                                />
                                {errors.email && (
                                    <span className="khidmati-form__error">{errors.email}</span>
                                )}
                            </label>

                            <label>
                                <span>
                                    {__('website.contact.message')}
                                    <span aria-hidden="true"> *</span>
                                </span>
                                <textarea
                                    name="message"
                                    value={data.message}
                                    onChange={(e) => setData('message', e.target.value)}
                                    placeholder={__('website.contact.message')}
                                    aria-label={__('website.contact.message')}
                                    style={{ textAlign: align }}
                                    required
                                />
                                {errors.message && (
                                    <span className="khidmati-form__error">
                                        {errors.message}
                                    </span>
                                )}
                            </label>

                            <div>
                                <button
                                    type="submit"
                                    className="khidmati-btn khidmati-btn--primary"
                                    disabled={processing}
                                >
                                    {processing
                                        ? __('website.contact.sending')
                                        : __('website.contact.submit')}
                                </button>
                            </div>
                        </form>
                    </div>

                    <aside className="khidmati-prose">
                        <div>
                            <h2>{__('website.contact.info_title')}</h2>
                            <p>{__('website.contact.info_text')}</p>
                            <p>
                                <Link
                                    href={route('login')}
                                    className="khidmati-btn khidmati-btn--outline-dark"
                                >
                                    {__('website.contact.info_login')}
                                </Link>
                            </p>
                        </div>
                    </aside>
                </div>
            </section>
        </MarketingLayout>
    );
}
