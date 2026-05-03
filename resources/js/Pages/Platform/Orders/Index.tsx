import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
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
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    {t.page_title ?? 'Orders'}
                </h2>
            }
        >
            <Head title={t.page_title ?? 'Orders'} />

            <div className="py-10">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg bg-white shadow">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        {t.col_order ?? 'Order'}
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        {t.col_company ?? 'Company'}
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        {t.col_customer ?? 'Customer'}
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        {t.col_status ?? 'Status'}
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 bg-white">
                                {orders.data.map((o) => (
                                    <tr key={o.id}>
                                        <td className="px-4 py-3 text-sm text-gray-900">{o.order_number}</td>
                                        <td className="px-4 py-3 text-sm text-gray-700">
                                            {localizedName(locale, o.company)}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-700">
                                            {localizedName(locale, o.customer?.user ?? null)}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-700">
                                            {statusLabels[o.status] ?? o.status}
                                        </td>
                                    </tr>
                                ))}
                                {orders.data.length === 0 && (
                                    <tr>
                                        <td colSpan={4} className="px-4 py-8 text-center text-sm text-gray-500">
                                            {t.empty ?? 'No orders found.'}
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </PlatformAdminLayout>
    );
}
