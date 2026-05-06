import MainLayout from '@/Layouts/Tenant/MainLayout.jsx';
import type { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';
import type { PropsWithChildren, ReactNode } from 'react';

export default function CompanyDashboardLayout({
    header,
    children,
    breadcrumbKey,
}: PropsWithChildren<{
    header?: ReactNode;
    breadcrumbKey?: string;
}>) {
    const user = usePage<PageProps>().props.auth.user;

    if (!user) {
        return (
            <div className="container py-5">
                <div className="alert alert-warning mb-0">
                    Session is missing. Please <a href="/login">sign in</a> again.
                </div>
            </div>
        );
    }

    return (
        <MainLayout
            shellVariant="company"
            title="page"
        >
            {children}
        </MainLayout>
    );
}
