import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import DataTable from '@/Components/ui/DataTable';
import Pagination from '@/Components/ui/Pagination';
import StatusBadge from '@/Components/ui/StatusBadge';
import TableCard from '@/Components/ui/TableCard';
import type { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';

type Row = {
    id: number;
    order_number: string;
    status: string;
    company: { name: string; name_ar?: string | null; name_en?: string | null } | null;
    customer: { user: { name: string; name_ar?: string | null; name_en?: string | null } } | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
};

function localizedName(
    locale: string,
    row: { name: string; name_ar?: string | null; name_en?: string | null } | null | undefined,
): string {
    if (!row) {
        return '—';
    }
    if (locale === 'en') {
        return row.name_en || row.name_ar || row.name;
    }
    return row.name_ar || row.name_en || row.name;
}

export default function PlatformOrdersIndex({ orders }: { orders: Paginated<Row> }) {
    const page = usePage<PageProps>().props;
    const t = page.t?.orders ?? {};
    const locale = page.locale;
    const statusLabels = page.enumLabels?.order_status ?? {};

    return (
        <PlatformAdminLayout
            header={
                <h1 className="m-0 text-2xl font-weight-bold text-dark">
                    {t.page_title ?? 'Orders'}
                </h1>
            }
        >
            <Head title={t.page_title ?? 'Orders'} />

            <TableCard bodyPadding={false}>
                <DataTable<Row>
                    columns={[
                        {
                            key: 'order',
                            header: t.col_order ?? 'Order',
                            render: (o) => o.order_number,
                        },
                        {
                            key: 'company',
                            header: t.col_company ?? 'Company',
                            render: (o) => localizedName(locale, o.company),
                        },
                        {
                            key: 'customer',
                            header: t.col_customer ?? 'Customer',
                            render: (o) => localizedName(locale, o.customer?.user ?? null),
                        },
                        {
                            key: 'status',
                            header: t.col_status ?? 'Status',
                            render: (o) => (
                                <StatusBadge variant="info">
                                    {statusLabels[o.status] ?? o.status}
                                </StatusBadge>
                            ),
                        },
                    ]}
                    rows={orders.data}
                    rowKey={(o) => o.id}
                    emptyTitle={t.empty ?? 'No orders found.'}
                />

                <div className="px-3 pb-3">
                    <Pagination links={orders.links} data={orders} />
                </div>
            </TableCard>
        </PlatformAdminLayout>
    );
}
