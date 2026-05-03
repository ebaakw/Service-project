import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

type Reply = {
    id: number;
    message: string;
    is_internal: boolean;
    user_name: string | undefined;
    created_at: string | undefined;
};

export default function PlatformFeedbackShow({
    ticket,
    replies,
    staffUsers,
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
        company: string | undefined;
        order: { id: number; order_number: string } | null;
        assigned_to_user_id: number | null;
    };
    replies: Reply[];
    staffUsers: { id: number; name: string }[];
}) {
    const ticketForm = useForm({
        status: ticket.status,
        priority: ticket.priority,
        assigned_to_user_id: ticket.assigned_to_user_id === null ? '' : String(ticket.assigned_to_user_id),
    });

    const replyForm = useForm({ message: '', is_internal: false });

    const updateTicket = (e: React.FormEvent) => {
        e.preventDefault();
        ticketForm.patch(route('platform.feedback-tickets.update', ticket.ulid));
    };

    const sendReply = (e: React.FormEvent) => {
        e.preventDefault();
        replyForm.post(route('platform.feedback-tickets.replies.store', ticket.ulid));
    };

    return (
        <PlatformAdminLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Feedback ticket</h2>}>
            <Head title={ticket.subject} />

            <div className="py-10">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link href={route('platform.feedback-tickets.index')} className="text-sm text-indigo-700 hover:text-indigo-900">
                            ← Back
                        </Link>
                    </div>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <p className="text-xs uppercase tracking-wide text-gray-500">{ticket.type}</p>
                        <h1 className="text-xl font-semibold text-gray-900">{ticket.subject}</h1>
                        <p className="mt-2 text-sm text-gray-600">Customer: {ticket.customer ?? '—'}</p>
                        <p className="text-sm text-gray-600">Company: {ticket.company ?? '—'}</p>
                        {ticket.order && <p className="text-sm text-gray-600">Order: {ticket.order.order_number}</p>}
                        <div className="mt-4 whitespace-pre-wrap border-t border-gray-100 pt-4 text-sm text-gray-800">{ticket.message}</div>
                    </div>

                    <form onSubmit={updateTicket} className="mt-6 space-y-4 rounded-lg bg-white p-6 shadow">
                        <h3 className="font-medium text-gray-900">Assignment</h3>
                        <div className="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label className="block text-xs font-medium text-gray-600">Status</label>
                                <select
                                    className="mt-1 w-full rounded-md border border-gray-300 px-2 py-2 text-sm"
                                    value={ticketForm.data.status}
                                    onChange={(e) => ticketForm.setData('status', e.target.value)}
                                >
                                    <option value="open">open</option>
                                    <option value="in_progress">in_progress</option>
                                    <option value="resolved">resolved</option>
                                    <option value="closed">closed</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-600">Priority</label>
                                <select
                                    className="mt-1 w-full rounded-md border border-gray-300 px-2 py-2 text-sm"
                                    value={ticketForm.data.priority}
                                    onChange={(e) => ticketForm.setData('priority', e.target.value)}
                                >
                                    <option value="low">low</option>
                                    <option value="normal">normal</option>
                                    <option value="high">high</option>
                                    <option value="urgent">urgent</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-600">Assigned to</label>
                                <select
                                    className="mt-1 w-full rounded-md border border-gray-300 px-2 py-2 text-sm"
                                    value={ticketForm.data.assigned_to_user_id}
                                    onChange={(e) => ticketForm.setData('assigned_to_user_id', e.target.value)}
                                >
                                    <option value="">—</option>
                                    {staffUsers.map((u) => (
                                        <option key={u.id} value={u.id}>
                                            {u.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        <button
                            type="submit"
                            disabled={ticketForm.processing}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                        >
                            Save changes
                        </button>
                    </form>

                    <div className="mt-6 rounded-lg bg-white p-6 shadow">
                        <h3 className="font-medium text-gray-900">Thread</h3>
                        <ul className="mt-4 space-y-4">
                            {replies.map((r) => (
                                <li key={r.id} className="border-b border-gray-100 pb-4 text-sm">
                                    <p className="font-medium text-gray-800">
                                        {r.user_name ?? 'Staff'}{' '}
                                        {r.is_internal && (
                                            <span className="rounded bg-amber-100 px-2 py-0.5 text-xs text-amber-900">internal</span>
                                        )}
                                    </p>
                                    <p className="text-xs text-gray-500">{r.created_at}</p>
                                    <p className="mt-2 whitespace-pre-wrap text-gray-700">{r.message}</p>
                                </li>
                            ))}
                        </ul>

                        <form onSubmit={sendReply} className="mt-6 border-t border-gray-100 pt-4">
                            <label className="block text-sm font-medium text-gray-700">Reply</label>
                            <textarea
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm"
                                rows={4}
                                value={replyForm.data.message}
                                onChange={(e) => replyForm.setData('message', e.target.value)}
                            />
                            <label className="mt-2 flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    checked={replyForm.data.is_internal}
                                    onChange={(e) => replyForm.setData('is_internal', e.target.checked)}
                                />
                                Internal note (hidden from company)
                            </label>
                            {replyForm.errors.message && <p className="mt-1 text-sm text-red-600">{replyForm.errors.message}</p>}
                            <button
                                type="submit"
                                disabled={replyForm.processing}
                                className="mt-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                            >
                                Send
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </PlatformAdminLayout>
    );
}
