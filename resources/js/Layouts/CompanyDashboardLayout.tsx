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
        return null;
    }

    return (
        <MainLayout
            shellVariant="company"
            title="page"
            name={breadcrumbKey ?? ''}
            hideBreadcrumbTitle={Boolean(header)}
        >
            {header ? (
                <div className="row mb-3">
                    <div className="col-12">{header}</div>
                </div>
            ) : null}
            {children}
        </MainLayout>
    );
}
