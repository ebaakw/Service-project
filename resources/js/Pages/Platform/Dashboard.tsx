import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import type { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';

type PlatformStats = {
    companies?: number | null;
    companies_active?: number | null;
    customers?: number | null;
    technicians?: number | null;
    orders?: number | null;
    orders_pending?: number | null;
    invoices?: number | null;
    invoices_unpaid?: number | null;
    feedback_open?: number | null;
};

type DashboardProps = PageProps<{
    stats?: PlatformStats;
}>;

function tr(
    t: PageProps['t'] | undefined,
    key: string,
    fallback: string,
): string {
    if (!t) {
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

function fmt(n: number | null | undefined): string {
    if (typeof n !== 'number' || Number.isNaN(n)) {
        return '—';
    }
    return new Intl.NumberFormat().format(n);
}

export default function PlatformDashboard() {
    const { t, locale, stats } = usePage<DashboardProps>().props;
    const s: PlatformStats = stats ?? {};

    const cards: ReadonlyArray<{
        key: keyof PlatformStats;
        labelKey: string;
        labelFallback: string;
        color: string;
        icon: string;
    }> = [
        {
            key: 'companies',
            labelKey: 'nav.all_companies',
            labelFallback: 'All Companies',
            color: 'bg-info',
            icon: 'fa-building',
        },
        {
            key: 'customers',
            labelKey: 'nav.all_customers',
            labelFallback: 'All Customers',
            color: 'bg-primary',
            icon: 'fa-user-friends',
        },
        {
            key: 'technicians',
            labelKey: 'nav.all_technicians',
            labelFallback: 'All Technicians',
            color: 'bg-success',
            icon: 'fa-user-cog',
        },
        {
            key: 'orders',
            labelKey: 'nav.all_orders',
            labelFallback: 'All Orders',
            color: 'bg-warning',
            icon: 'fa-shopping-bag',
        },
        {
            key: 'invoices',
            labelKey: 'nav.all_invoices',
            labelFallback: 'All Invoices',
            color: 'bg-danger',
            icon: 'fa-file-invoice',
        },
        {
            key: 'feedback_open',
            labelKey: 'nav.platform_feedback',
            labelFallback: 'Platform Feedback',
            color: 'bg-navy',
            icon: 'fa-comment-dots',
        },
    ];

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.platform_dashboard"
            header={
                <h1 className="m-0 text-2xl font-weight-bold text-dark">
                    {tr(t, 'nav.platform_dashboard', 'Platform Dashboard')}
                </h1>
            }
        >
            <Head
                title={
                    locale === 'ar'
                        ? 'لوحة تحكم المنصة'
                        : 'Platform Dashboard'
                }
            />

            <div className="row">
                {cards.map((c) => (
                    <div className="col-lg-4 col-md-6 col-12 mb-3" key={c.key}>
                        <div className={`small-box ${c.color}`}>
                            <div className="inner">
                                <h3 className="m-0">{fmt(s[c.key])}</h3>
                                <p className="m-0 text-sm">
                                    {tr(t, c.labelKey, c.labelFallback)}
                                </p>
                            </div>
                            <div className="icon">
                                <i className={`fas ${c.icon}`} />
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            <div className="row">
                <div className="col-md-6 col-12 mb-3">
                    <div className="small-box bg-light">
                        <div className="inner text-dark">
                            <h3 className="m-0">{fmt(s.companies_active)}</h3>
                            <p className="m-0 text-sm">
                                {locale === 'ar' ? 'الشركات النشطة' : 'Active Companies'}
                            </p>
                        </div>
                        <div className="icon">
                            <i className="fas fa-check-circle" />
                        </div>
                    </div>
                </div>
                <div className="col-md-6 col-12 mb-3">
                    <div className="small-box bg-light">
                        <div className="inner text-dark">
                            <h3 className="m-0">{fmt(s.orders_pending)}</h3>
                            <p className="m-0 text-sm">
                                {locale === 'ar' ? 'الطلبات قيد المعالجة' : 'Orders In Progress'}
                            </p>
                        </div>
                        <div className="icon">
                            <i className="fas fa-hourglass-half" />
                        </div>
                    </div>
                </div>
            </div>
        </PlatformAdminLayout>
    );
}
