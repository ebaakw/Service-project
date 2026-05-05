import MainLayout from '@/Layouts/Tenant/MainLayout.jsx';
import type { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';
import type { PropsWithChildren, ReactNode } from 'react';

export default function PlatformAdminLayout({
    header,
    children,
    breadcrumbKey,
}: PropsWithChildren<{
    header?: ReactNode;
    /** Breadcrumb translation key, e.g. dashboard.nav.overview */
    breadcrumbKey?: string;
}>) {
    const user = usePage<PageProps>().props.auth.user;

    if (!user) {
        return null;
    }

    return (
        <MainLayout
            shellVariant="platform"
            title="page"
        >
            {children}
        </MainLayout>
    );
}
