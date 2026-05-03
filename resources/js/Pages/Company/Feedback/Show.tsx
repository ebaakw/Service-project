import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import { Head, Link, useForm } from '@inertiajs/react';

type Reply = {
    id: number;
    message: string;
    user_name: string | undefined;
    created_at: string | undefined;
};

export default function CompanyFeedbackShow({
    ticket,
    replies,
}: {
    ticket: {
        ulid: string;
        type: string;
        subject: string;
        message: string;
        status: string;
        priority: string;
        resolved_at: string | null;
        customer: string | undefined;
        order: { id: number; order_number: string } | null;
    };
    replies: Reply[];
}) {
    const replyForm = useForm({ message: '' });
    const statusForm = useForm({ status: ticket.status });

    const sendReply = (e: React.FormEvent) => {
        e.preventDefault();
        replyForm.post(route('company.feedback-tickets.replies.store', ticket.ulid));
    };

    const updateStatus = (e: React.FormEvent) => {
        e.preventDefault();
        statusForm.patch(route('company.feedback-tickets.update', ticket.ulid));
    };

    return (
        <CompanyDashboardLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Feedback ticket</h2>}>
            <Head title={ticket.subject} />

            <div className="py-10">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link href={route('company.feedback-tickets.index')} className="text-sm text-emerald-700 hover:text-emerald-900">
                            ← Back
                        </Link>
                    </div>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <p className="text-xs uppercase tracking-wide text-gray-500">{ticket.type}</p>
                        <h1 className="text-xl font-semibold text-gray-900">{ticket.subject}</h1>
                        <p className="mt-2 text-sm text-gray-600">Status: {ticket.status}</p>
                        <p className="text-sm text-gray-600">Priority: {ticket.priority}</p>
                        {ticket.customer && <p className="text-sm text-gray-600">Customer: {ticket.customer}</p>}
                        {ticket.order && <p className="text-sm text-gray-600">Order: {ticket.order.order_number}</p>}
                        <div className="mt-4 whitespace-pre-wrap border-t border-gray-100 pt-4 text-sm text-gray-800">{ticket.message}</div>
                    </div>

                    <div className="mt-6 rounded-lg bg-white p-6 shadow">
                        <h3 className="font-medium text-gray-900">Update status</h3>
                        <form onSubmit={updateStatus} className="mt-2 flex flex-wrap items-end gap-4">
                            <select
                                className="rounded-md border border-gray-300 px-3 py-2 text-sm"
                                value={statusForm.data.status}
                                onChange={(e) => statusForm.setData('status', e.target.value)}
                            >
                                <option value="open">open</option>
                                <option value="in_progress">in_progress</option>
                                <option value="resolved">resolved</option>
                                <option value="closed">closed</option>
                            </select>
                            <button
                                type="submit"
                                disabled={statusForm.processing}
                                className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                            >
                                Save
                            </button>
                        </form>
                    </div>

                    <div className="mt-6 rounded-lg bg-white p-6 shadow">
                        <h3 className="font-medium text-gray-900">Replies</h3>
                        <ul className="mt-4 space-y-4">
                            {replies.map((r) => (
                                <li key={r.id} className="border-b border-gray-100 pb-4 text-sm">
                                    <p className="font-medium text-gray-800">{r.user_name ?? 'Staff'}</p>
                                    <p className="text-xs text-gray-500">{r.created_at}</p>
                                    <p className="mt-2 whitespace-pre-wrap text-gray-700">{r.message}</p>
                                </li>
                            ))}
                        </ul>

                        <form onSubmit={sendReply} className="mt-6 border-t border-gray-100 pt-4">
                            <label className="block text-sm font-medium text-gray-700">Your reply</label>
                            <textarea
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                rows={4}
                                value={replyForm.data.message}
                                onChange={(e) => replyForm.setData('message', e.target.value)}
                            />
                            {replyForm.errors.message && <p className="mt-1 text-sm text-red-600">{replyForm.errors.message}</p>}
                            <button
                                type="submit"
                                disabled={replyForm.processing}
                                className="mt-2 rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                            >
                                Send reply
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </CompanyDashboardLayout>
    );
}
