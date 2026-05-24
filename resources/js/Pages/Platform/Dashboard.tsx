import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import StatCard from '@/Components/Dashboard/StatCard';
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
    const isRtl = locale === 'ar';

    const cards: ReadonlyArray<{
        key: keyof PlatformStats;
        labelKey: string;
        labelFallback: string;
        color: string;
        icon: string;
        href: string;
    }> = [
        {
            key: 'companies',
            labelKey: 'nav.all_companies',
            labelFallback: 'All Companies',
            color: 'bg-info',
            icon: 'fa-building',
            href: route('platform.companies.index'),
        },
        {
            key: 'companies_active',
            labelKey: 'nav.all_companies',
            labelFallback: locale === 'ar' ? 'الشركات النشطة' : 'Active Companies',
            color: 'bg-success',
            icon: 'fa-check-circle',
            href: route('platform.companies.index'),
        },
        {
            key: 'customers',
            labelKey: 'nav.all_customers',
            labelFallback: 'All Customers',
            color: 'bg-primary',
            icon: 'fa-user-friends',
            href: route('platform.customers.index'),
        },
        {
            key: 'technicians',
            labelKey: 'nav.all_technicians',
            labelFallback: 'All Technicians',
            color: 'bg-success',
            icon: 'fa-user-cog',
            href: route('platform.technicians.index'),
        },
        {
            key: 'orders',
            labelKey: 'nav.all_orders',
            labelFallback: 'All Orders',
            color: 'bg-warning',
            icon: 'fa-shopping-bag',
            href: route('platform.orders.index'),
        },
        {
            key: 'orders_pending',
            labelKey: 'nav.all_orders',
            labelFallback: locale === 'ar' ? 'الطلبات قيد المعالجة' : 'Orders In Progress',
            color: 'bg-secondary',
            icon: 'fa-hourglass-half',
            href: route('platform.orders.index'),
        },
        {
            key: 'invoices',
            labelKey: 'nav.all_invoices',
            labelFallback: 'All Invoices',
            color: 'bg-danger',
            icon: 'fa-file-invoice',
            href: route('platform.invoices.index'),
        },
        {
            key: 'feedback_open',
            labelKey: 'nav.platform_feedback',
            labelFallback: 'Platform Feedback',
            color: 'bg-navy',
            icon: 'fa-comment-dots',
            href: route('platform.feedback-tickets.index'),
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
                        ? 'لوحة تحكم الأدمن'
                        : 'Platform Dashboard'
                }
            />

            <div className="row">
                {cards.map((c) => (
                    <div className="col-lg-4 col-md-6 col-12 mb-3" key={c.key}>
                        <StatCard
                            label={tr(t, c.labelKey, c.labelFallback)}
                            value={fmt(s[c.key])}
                            icon={c.icon}
                            color={c.color}
                            href={c.href}
                            isRtl={isRtl}
                            moreLabel={tr(t, 'actions.view_more', 'View more')}
                        />
                    </div>
                ))}
            </div>
        </PlatformAdminLayout>
    );
}
