import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { Head } from '@inertiajs/react';

type Row = {
    id: number;
    invoice_number: string;
    status: string;
    total_amount: string;
    company: { name: string } | null;
    customer: { user: { name: string } } | null;
    order: { order_number: string } | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
};

export default function PlatformInvoicesIndex({ invoices }: { invoices: Paginated<Row> }) {
    return (
        <PlatformAdminLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Invoices</h2>}>
            <Head title="Invoices" />

            <div className="py-10">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg bg-white shadow">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Invoice
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Order
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Company
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Customer
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Total
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 bg-white">
                                {invoices.data.map((inv) => (
                                    <tr key={inv.id}>
                                        <td className="px-4 py-3 text-sm text-gray-900">{inv.invoice_number}</td>
                                        <td className="px-4 py-3 text-sm text-gray-700">{inv.order?.order_number ?? '—'}</td>
                                        <td className="px-4 py-3 text-sm text-gray-700">{inv.company?.name ?? '—'}</td>
                                        <td className="px-4 py-3 text-sm text-gray-700">{inv.customer?.user.name ?? '—'}</td>
                                        <td className="px-4 py-3 text-sm text-gray-700">{inv.total_amount}</td>
                                        <td className="px-4 py-3 text-sm text-gray-700">{inv.status}</td>
                                    </tr>
                                ))}
                                {invoices.data.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-8 text-center text-sm text-gray-500">
                                            No invoices found.
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
