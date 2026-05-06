import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import StatCard from '@/Components/Dashboard/StatCard';
import type { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';

type CompanyStats = {
    orders?: number | null;
    orders_pending?: number | null;
    technicians?: number | null;
    invoices?: number | null;
    invoices_unpaid?: number | null;
    feedback_open?: number | null;
    portfolio?: number | null;
};

type DashboardProps = PageProps<{
    stats?: CompanyStats;
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

export default function CompanyDashboard() {
    const { t, locale, stats, currentCompany } = usePage<DashboardProps>().props;
    const s: CompanyStats = stats ?? {};
    const isRtl = locale === 'ar';

    const cards: ReadonlyArray<{
        key: keyof CompanyStats;
        labelKey: string;
        labelFallback: string;
        color: string;
        icon: string;
    }> = [
        {
            key: 'orders',
            labelKey: 'nav.company_orders',
            labelFallback: 'Company Orders',
            color: 'bg-warning',
            icon: 'fa-shopping-bag',
        },
        {
            key: 'orders_pending',
            labelKey: 'nav.company_orders',
            labelFallback: 'Pending Orders',
            color: 'bg-secondary',
            icon: 'fa-hourglass-half',
        },
        {
            key: 'technicians',
            labelKey: 'nav.company_technicians',
            labelFallback: 'Company Technicians',
            color: 'bg-success',
            icon: 'fa-user-cog',
        },
        {
            key: 'invoices_unpaid',
            labelKey: 'nav.all_invoices',
            labelFallback: 'Unpaid Invoices',
            color: 'bg-danger',
            icon: 'fa-file-invoice',
        },
        {
            key: 'portfolio',
            labelKey: 'nav.company_portfolio',
            labelFallback: 'Company Portfolio',
            color: 'bg-info',
            icon: 'fa-briefcase',
        },
        {
            key: 'feedback_open',
            labelKey: 'nav.company_feedback',
            labelFallback: 'Company Feedback',
            color: 'bg-primary',
            icon: 'fa-comment-dots',
        },
    ];

    return (
        <CompanyDashboardLayout
            breadcrumbKey="dashboard.nav.company_dashboard"
            header={
                <h1 className="m-0 text-2xl font-weight-bold text-dark">
                    {currentCompany?.display_name
                        ? `${tr(t, 'nav.company_dashboard', 'Company Dashboard')} — ${currentCompany.display_name}`
                        : tr(t, 'nav.company_dashboard', 'Company Dashboard')}
                </h1>
            }
        >
            <Head
                title={
                    locale === 'ar'
                        ? 'لوحة تحكم الشركة'
                        : 'Company Dashboard'
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
                            isRtl={isRtl}
                        />
                    </div>
                ))}
            </div>
        </CompanyDashboardLayout>
    );
}
