import type { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';

declare global {
    interface Window {
        __inertia_page?: { props?: Record<string, unknown> };
    }
}

export default function HtmlLocale() {
    const page = usePage<PageProps>();
    const { locale, direction } = page.props;

    // Keep __() in sync for Breeze pages (app.tsx) that lack <x-translations />.
    if (typeof window !== 'undefined') {
        window.__inertia_page = { props: page.props as unknown as Record<string, unknown> };
    }

    useEffect(() => {
        document.documentElement.lang = String(locale).replace('_', '-');
        document.documentElement.dir = direction;
    }, [locale, direction]);

    return null;
}
