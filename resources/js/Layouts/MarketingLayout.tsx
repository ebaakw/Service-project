import { PageProps } from '@/types';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

type NavKey = 'home' | 'about' | 'features' | 'contact';

export default function MarketingLayout({
    children,
    active,
}: PropsWithChildren<{ active: NavKey }>) {
    const { auth, locale, direction } = usePage<PageProps>().props;
    const oppositeLocale = locale === 'ar' ? 'en' : 'ar';

    const navItems: Array<{ key: NavKey; href: string; label: string }> = [
        { key: 'home', href: route('website.home'), label: __('website.nav.home') },
        { key: 'about', href: route('website.about'), label: __('website.nav.about') },
        {
            key: 'features',
            href: route('website.features'),
            label: __('website.nav.features'),
        },
        {
            key: 'contact',
            href: route('website.contact'),
            label: __('website.nav.contact'),
        },
    ];

    return (
        <div className="khidmati-site" dir={direction}>
            <header className="khidmati-site__nav">
                <div className="khidmati-site__nav-inner">
                    <Link href={route('website.home')} className="khidmati-site__brand">
                        {__('website.brand')}
                    </Link>

                    <nav className="khidmati-site__links" aria-label="Primary">
                        {navItems.map((item) => (
                            <Link
                                key={item.key}
                                href={item.href}
                                className={active === item.key ? 'is-active' : undefined}
                            >
                                {item.label}
                            </Link>
                        ))}
                    </nav>

                    <div className="khidmati-site__actions">
                        <a
                            href={route('locale.switch', { locale: oppositeLocale })}
                            className="khidmati-btn khidmati-btn--ghost khidmati-btn--lang"
                        >
                            {__('website.nav.language')}
                        </a>

                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="khidmati-btn khidmati-btn--primary"
                            >
                                {__('website.nav.dashboard')}
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={route('login')}
                                    className="khidmati-btn khidmati-btn--ghost"
                                >
                                    {__('website.nav.login')}
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="khidmati-btn khidmati-btn--primary"
                                >
                                    {__('website.nav.register')}
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </header>

            <main className="khidmati-site__main">{children}</main>

            <footer className="khidmati-site__footer">
                <div className="khidmati-site__footer-inner">
                    <div>
                        <div className="khidmati-site__footer-brand">
                            {__('website.brand')}
                        </div>
                        <p>{__('website.footer.tagline')}</p>
                        <p>
                            © {new Date().getFullYear()} {__('website.brand')}.{' '}
                            {__('website.footer.rights')}
                        </p>
                    </div>
                    <div className="khidmati-site__footer-links">
                        {navItems.map((item) => (
                            <Link key={item.key} href={item.href}>
                                {item.label}
                            </Link>
                        ))}
                    </div>
                </div>
            </footer>
        </div>
    );
}
