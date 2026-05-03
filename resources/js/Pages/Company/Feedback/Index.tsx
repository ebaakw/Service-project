import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import { Head, Link } from '@inertiajs/react';

type Row = {
    ulid: string;
    type: string;
    subject: string;
    status: string;
    priority: string;
    customer: { user?: { name: string } } | null;
};

type Paginated<T> = { data: T[] };

export default function CompanyFeedbackIndex({
    tickets,
    filters,
}: {
    tickets: Paginated<Row>;
    filters: { status: string };
}) {
    return (
        <CompanyDashboardLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Feedback</h2>}>
            <Head title="Feedback" />

            <div className="py-10">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link href={route('company.dashboard')} className="text-sm text-emerald-700 hover:text-emerald-900">
                            ← Dashboard
                        </Link>
                    </div>

                    <div className="overflow-hidden rounded-lg bg-white shadow">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Subject</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Type</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Customer</th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 bg-white">
                                {tickets.data.length === 0 && (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-8 text-center text-sm text-gray-500">
                                            No tickets.
                                        </td>
                                    </tr>
                                )}
                                {tickets.data.map((t) => (
                                    <tr key={t.ulid}>
                                        <td className="px-4 py-3 text-sm text-gray-900">{t.subject}</td>
                                        <td className="px-4 py-3 text-sm text-gray-600">{t.type}</td>
                                        <td className="px-4 py-3 text-sm text-gray-600">{t.status}</td>
                                        <td className="px-4 py-3 text-sm text-gray-600">{t.customer?.user?.name ?? '—'}</td>
                                        <td className="px-4 py-3 text-right text-sm">
                                            <Link
                                                href={route('company.feedback-tickets.show', t.ulid)}
                                                className="text-emerald-700 hover:text-emerald-900"
                                            >
                                                Open
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
