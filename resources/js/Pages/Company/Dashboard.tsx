import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import type { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';

function tr(
    t: PageProps['t'] | undefined,
    key: string,
    fallback: string,
): string {
    if (!t?.nav) {
        return fallback;
    }
    const parts = key.split('.');
    let cur: unknown = t;
    for (const p of parts) {
        if (cur !== null && typeof cur === 'object' && p in (cur as object)) {
            cur = (cur as Record<string, unknown>)[p];
        } else {
            return fallback;
        }
    }
    return typeof cur === 'string' ? cur : fallback;
}

export default function CompanyDashboard() {
    const { t, locale } = usePage<PageProps>().props;

    return (
        <CompanyDashboardLayout
            breadcrumbKey="dashboard.nav.overview"
            header={
                <h1 className="m-0 text-2xl font-weight-bold text-dark">
                    {tr(t, 'nav.overview', 'Overview')}
                </h1>
            }
        >
            <Head
                title={
                    locale === 'ar'
                        ? 'لوحة الشركة'
                        : 'Company dashboard'
                }
            />

            <div className="row">
                {(
                    [
                        { key: 'orders', color: 'bg-warning', icon: 'fa-shopping-bag' },
                        { key: 'technicians', color: 'bg-success', icon: 'fa-user-cog' },
                        { key: 'portfolio', color: 'bg-info', icon: 'fa-briefcase' },
                        { key: 'feedback', color: 'bg-primary', icon: 'fa-comment-dots' },
                        { key: 'price_lists', color: 'bg-secondary', icon: 'fa-list-alt' },
                        { key: 'discounts', color: 'bg-danger', icon: 'fa-percent' },
                    ] as const
                ).map((c) => (
                    <div className="col-lg-4 col-md-6 col-12 mb-3" key={c.key}>
                        <div className={`small-box ${c.color}`}>
                            <div className="inner">
                                <h3 className="m-0">—</h3>
                                <p className="m-0 text-sm">
                                    {tr(t, `nav.${c.key}`, c.key)}
                                </p>
                                <p className="m-0 mt-1 text-xs text-white-50">
                                    {/* TODO: wire counts from CompanyDashboardController when available */}
                                </p>
                            </div>
                            <div className="icon">
                                <i className={`fas ${c.icon}`} />
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            <div className="card card-outline card-secondary">
                <div className="card-body text-muted">
                    {locale === 'ar'
                        ? 'منطقة عمل الشركة — سيتم عرض الإحصائيات عند ربطها من الخادم.'
                        : 'Company workspace — stats will appear when the backend provides them.'}
                </div>
            </div>
        </CompanyDashboardLayout>
    );
}
