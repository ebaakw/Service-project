import ApplicationLogo from '@/Components/ApplicationLogo';
import NavLink from '@/Components/NavLink';
import type { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode } from 'react';

export default function CompanyDashboardLayout({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const user = usePage<PageProps>().props.auth.user;

    if (!user) {
        return null;
    }

    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="border-b border-gray-100 bg-white">
                <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                    <div className="flex items-center gap-8">
                        <Link href="/">
                            <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800" />
                        </Link>
                        <span className="text-sm font-semibold uppercase tracking-wide text-emerald-700">
                            Company
                        </span>
                        <NavLink
                            href={route('company.dashboard')}
                            active={route().current('company.dashboard')}
                        >
                            Overview
                        </NavLink>
                        <NavLink
                            href={route('company.orders.index')}
                            active={Boolean(route().current('company.orders.*'))}
                        >
                            Orders
                        </NavLink>
                        <NavLink
                            href={route('company.technicians.index')}
                            active={Boolean(route().current('company.technicians.*'))}
                        >
                            Technicians
                        </NavLink>
                        <NavLink
                            href={route('company.settings.edit')}
                            active={Boolean(route().current('company.settings.*'))}
                        >
                            Settings
                        </NavLink>
                        <NavLink
                            href={route('company.portfolio-projects.index')}
                            active={Boolean(route().current('company.portfolio-projects.*'))}
                        >
                            Portfolio
                        </NavLink>
                        <NavLink
                            href={route('company.feedback-tickets.index')}
                            active={Boolean(route().current('company.feedback-tickets.*'))}
                        >
                            Feedback
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
                        <span>{user.name}</span>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="text-emerald-700 hover:text-emerald-900"
                        >
                            Log out
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
