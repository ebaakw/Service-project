import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import { Head, Link } from '@inertiajs/react';

type OrderRow = {
    id: number;
    order_number: string;
    status: string;
    inspection_payment_status: string;
    final_payment_status: string;
    customer: { user: { name: string } };
    technician: { user: { name: string } } | null;
    service_category: { name_ar: string } | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
};

export default function CompanyOrdersIndex({ orders }: { orders: Paginated<OrderRow> }) {
    return (
        <CompanyDashboardLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Orders</h2>}>
            <Head title="Orders" />

            <div className="py-10">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="mb-6 flex items-center justify-between">
                        <Link href={route('company.dashboard')} className="text-sm text-emerald-700 hover:text-emerald-900">
                            ← Dashboard
                        </Link>
                    </div>

                    <div className="overflow-hidden rounded-lg bg-white shadow">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Order
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Customer
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Technician
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 bg-white">
                                {orders.data.length === 0 && (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-8 text-center text-sm text-gray-500">
                                            No orders yet.
                                        </td>
                                    </tr>
                                )}
                                {orders.data.map((o) => (
                                    <tr key={o.id}>
                                        <td className="px-4 py-3 text-sm text-gray-900">{o.order_number}</td>
                                        <td className="px-4 py-3 text-sm text-gray-600">{o.customer.user.name}</td>
                                        <td className="px-4 py-3 text-sm text-gray-600">{o.technician?.user.name ?? '—'}</td>
                                        <td className="px-4 py-3 text-sm text-gray-600">{o.status}</td>
                                        <td className="px-4 py-3 text-right text-sm">
                                            <Link
                                                href={route('company.orders.show', o.id)}
                                                className="text-emerald-700 hover:text-emerald-900"
                                            >
                                                View
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </CompanyDashboardLayout>
    );
}
