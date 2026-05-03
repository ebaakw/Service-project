import ApplicationLogo from '@/Components/ApplicationLogo';
import NavLink from '@/Components/NavLink';
import type { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode } from 'react';

export default function PlatformAdminLayout({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const page = usePage<PageProps>().props;
    const user = page.auth.user;
    const t = page.t;
    const locale = page.locale;

    if (!user) {
        return null;
    }

    const displayUserName =
        locale === 'en'
            ? user.name_en || user.name_ar || user.name
            : user.name_ar || user.name_en || user.name;

    const nav = (key: string, fallback: string) => {
        const k = key.split('.');
        let cur: unknown = t;
        for (const part of k) {
            if (cur !== null && typeof cur === 'object' && part in (cur as object)) {
                cur = (cur as Record<string, unknown>)[part];
            } else {
                return fallback;
            }
        }
        return typeof cur === 'string' ? cur : fallback;
    };

    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="border-b border-gray-100 bg-white">
                <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                    <div className="flex items-center gap-8">
                        <Link href="/">
                            <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800" />
                        </Link>
                        <span className="text-sm font-semibold uppercase tracking-wide text-indigo-700">
                            {nav('nav.platform_admin', 'Platform admin')}
                        </span>
                        <NavLink
                            href={route('platform.dashboard')}
                            active={route().current('platform.dashboard')}
                        >
                            {nav('nav.overview', 'Overview')}
                        </NavLink>
                        <NavLink
                            href={route('platform.orders.index')}
                            active={Boolean(route().current('platform.orders.*'))}
                        >
                            {nav('nav.orders', 'Orders')}
                        </NavLink>
                        <NavLink
                            href={route('platform.invoices.index')}
                            active={Boolean(route().current('platform.invoices.*'))}
                        >
                            {nav('nav.invoices', 'Invoices')}
                        </NavLink>
                        <NavLink
                            href={route('platform.customers.index')}
                            active={Boolean(route().current('platform.customers.*'))}
                        >
                            {nav('nav.customers', 'Customers')}
                        </NavLink>
                        <NavLink
                            href={route('platform.technicians.index')}
                            active={Boolean(route().current('platform.technicians.*'))}
                        >
                            {nav('nav.technicians', 'Technicians')}
                        </NavLink>
                        <NavLink
                            href={route('platform.service-categories.index')}
                            active={Boolean(route().current('platform.service-categories.*'))}
                        >
                            {nav('nav.service_catalog', 'Service catalog')}
                        </NavLink>
                        <NavLink
                            href={route('platform.settings.edit')}
                            active={Boolean(route().current('platform.settings.*'))}
                        >
                            {nav('nav.settings', 'Settings')}
                        </NavLink>
                        <NavLink
                            href={route('platform.portfolio-projects.index')}
                            active={Boolean(route().current('platform.portfolio-projects.*'))}
                        >
                            {nav('nav.portfolio', 'Portfolio')}
                        </NavLink>
                        <NavLink
                            href={route('platform.feedback-tickets.index')}
                            active={Boolean(route().current('platform.feedback-tickets.*'))}
                        >
                            {nav('nav.feedback', 'Feedback')}
                        </NavLink>
                    </div>
                    <div className="flex items-center gap-4 text-sm text-gray-600">
                        <Link
                            href={route('locale.switch', { locale: 'en' })}
                            className="hover:text-gray-900"
                        >
                            EN
                        </Link>
                        <span aria-hidden="true">|</span>
                        <Link
                            href={route('locale.switch', { locale: 'ar' })}
                            className="hover:text-gray-900"
                        >
                            عربي
                        </Link>
                        <span className="text-gray-400">·</span>
                        <span>{displayUserName}</span>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="text-indigo-600 hover:text-indigo-800"
                        >
                            {nav('nav.log_out', 'Log out')}
                        </Link>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="bg-white shadow">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}
