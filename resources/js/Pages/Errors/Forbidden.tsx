import type { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Forbidden() {
    const { locale, auth } = usePage<PageProps>().props;

    const title = locale === 'ar' ? 'غير مصرح' : 'Forbidden';
    const message =
        locale === 'ar'
            ? 'ليس لديك صلاحية للوصول إلى هذه الصفحة.'
            : 'You do not have permission to access this page.';

    const homeHref = auth?.user
        ? '/dashboard'
        : '/login';

    return (
        <div className="container py-5">
            <Head title={title} />
            <div className="card shadow-sm">
                <div className="card-body">
                    <h1 className="h3 mb-3">{title}</h1>
                    <p className="text-muted mb-4">{message}</p>
                    <div className="d-flex gap-2">
                        <Link className="btn btn-primary" href={homeHref}>
                            {locale === 'ar' ? 'العودة' : 'Go back'}
                        </Link>
                        <Link className="btn btn-outline-secondary" href="/">
                            {locale === 'ar' ? 'الرئيسية' : 'Home'}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}

