import type { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export default function HtmlLocale() {
    const { locale, direction } = usePage<PageProps>().props;

    useEffect(() => {
        document.documentElement.lang = String(locale).replace('_', '-');
        document.documentElement.dir = direction;
    }, [locale, direction]);

    return null;
}
