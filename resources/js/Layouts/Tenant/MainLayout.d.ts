import type { ReactNode } from 'react';

export default function MainLayout(props: {
    children: ReactNode;
    title?: string;
    name?: string;
    menuList?: unknown;
    shellVariant?: 'tenant' | 'platform' | 'company';
    hideBreadcrumbTitle?: boolean;
}): ReactNode;
