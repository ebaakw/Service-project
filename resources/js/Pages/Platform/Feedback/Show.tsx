import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import StatusBadge from '@/Components/ui/StatusBadge';
import TableCard from '@/Components/ui/TableCard';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';

const statusValues = ['open', 'in_progress', 'resolved', 'closed'];
const priorityValues = ['low', 'normal', 'high', 'urgent'];

function statusVariant(value: string) {
    if (value === 'resolved') return 'success' as const;
    if (value === 'in_progress') return 'info' as const;
    if (value === 'closed') return 'secondary' as const;
    return 'warning' as const;
}

function priorityVariant(value: string) {
    if (value === 'urgent') return 'danger' as const;
    if (value === 'high') return 'warning' as const;
    if (value === 'low') return 'secondary' as const;
    return 'info' as const;
}

function formatDate(value: string | null | undefined, locale: string) {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat(locale || undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
}

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
    const { locale, direction, enumLabels } = usePage<PageProps>().props;
    const isRtl = direction === 'rtl';
    const statusLabels = enumLabels?.feedback_ticket_status ?? {};
    const priorityLabels = enumLabels?.feedback_priority ?? {};
    const typeLabels = enumLabels?.feedback_ticket_type ?? {};
    const assignedUser = staffUsers.find((user) => user.id === ticket.assigned_to_user_id);

    const updateTicket = (e: FormEvent) => {
        e.preventDefault();
        ticketForm.patch(route('platform.feedback-tickets.update', ticket.ulid), { preserveScroll: true });
    };

    const sendReply = (e: FormEvent) => {
        e.preventDefault();
        replyForm.post(route('platform.feedback-tickets.replies.store', ticket.ulid), {
            preserveScroll: true,
            onSuccess: () => replyForm.reset(),
        });
    };

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.platform_feedback"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('dashboard.feedback_tickets.details_title')}</h4>}
        >
            <Head title={ticket.subject} />

            <div className="mb-3">
                <Link href={route('platform.feedback-tickets.index')} className="btn btn-outline-secondary btn-sm">
                    <i className={isRtl ? 'fas fa-arrow-right ml-1' : 'fas fa-arrow-left mr-1'} />
                    {__('dashboard.feedback_tickets.back_to_tickets')}
                </Link>
            </div>

            <div className="row">
                <div className="col-lg-8">
                    <TableCard
                        title={__('dashboard.feedback_tickets.ticket_summary')}
                        actions={
                            <div className="d-flex align-items-center gap-2 flex-wrap">
                                <StatusBadge variant="secondary" pill>{typeLabels[ticket.type] ?? ticket.type}</StatusBadge>
                                <StatusBadge variant={statusVariant(ticket.status)} pill>{statusLabels[ticket.status] ?? ticket.status}</StatusBadge>
                                <StatusBadge variant={priorityVariant(ticket.priority)} pill>{priorityLabels[ticket.priority] ?? ticket.priority}</StatusBadge>
                            </div>
                        }
                    >
                        <h5 className="font-weight-bold mb-3">{ticket.subject}</h5>
                        <div className="border rounded bg-light p-3" style={{ whiteSpace: 'pre-wrap' }}>
                            {ticket.message}
                        </div>
                    </TableCard>

                    <TableCard title={__('dashboard.feedback_tickets.thread')} variant="info">
                        {replies.length === 0 ? (
                            <div className="text-muted text-center py-4">{__('dashboard.feedback_tickets.no_replies')}</div>
                        ) : (
                            <div className="timeline mb-0">
                                {replies.map((reply) => (
                                    <div key={reply.id}>
                                        <i className={reply.is_internal ? 'fas fa-lock bg-warning' : 'fas fa-comment bg-info'} />
                                        <div className="timeline-item">
                                            <span className="time">
                                                <i className="far fa-clock mx-1" />
                                                {formatDate(reply.created_at, locale)}
                                            </span>
                                            <h3 className="timeline-header">
                                                <strong>{reply.user_name ?? __('dashboard.feedback_tickets.staff')}</strong>
                                                {reply.is_internal ? (
                                                    <StatusBadge variant="warning" className="mx-2">
                                                        {__('dashboard.feedback_tickets.internal')}
                                                    </StatusBadge>
                                                ) : null}
                                            </h3>
                                            <div className="timeline-body" style={{ whiteSpace: 'pre-wrap' }}>
                                                {reply.message}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                                <div>
                                    <i className="far fa-clock bg-gray" />
                                </div>
                            </div>
                        )}

                        <form onSubmit={sendReply} className="border-top pt-3 mt-3">
                            <div className="form-group">
                                <label htmlFor="feedback_reply">{__('dashboard.feedback_tickets.reply')}</label>
                                <textarea
                                    id="feedback_reply"
                                    className="form-control"
                                    rows={4}
                                    placeholder={__('dashboard.feedback_tickets.reply_placeholder')}
                                    value={replyForm.data.message}
                                    onChange={(e) => replyForm.setData('message', e.target.value)}
                                />
                                {replyForm.errors.message ? <div className="invalid-feedback d-block">{replyForm.errors.message}</div> : null}
                            </div>
                            <div className="form-check mb-3">
                                <input
                                    id="feedback_internal_note"
                                    type="checkbox"
                                    className="form-check-input"
                                    checked={replyForm.data.is_internal}
                                    onChange={(e) => replyForm.setData('is_internal', e.target.checked)}
                                />
                                <label className="form-check-label" htmlFor="feedback_internal_note">
                                    {__('dashboard.feedback_tickets.internal_note')}
                                </label>
                                <small className="form-text text-muted">{__('dashboard.feedback_tickets.internal_note_help')}</small>
                            </div>
                            <button type="submit" disabled={replyForm.processing} className="btn btn-primary">
                                <i className={isRtl ? 'fas fa-paper-plane ml-1' : 'fas fa-paper-plane mr-1'} />
                                {__('dashboard.feedback_tickets.send')}
                            </button>
                        </form>
                    </TableCard>
                </div>

                <div className="col-lg-4">
                    <TableCard title={__('dashboard.feedback_tickets.ticket_details')} variant="secondary">
                        <dl className="row mb-0">
                            <dt className="col-sm-5">{__('dashboard.feedback_tickets.customer')}</dt>
                            <dd className="col-sm-7">{ticket.customer ?? <span className="text-muted">—</span>}</dd>
                            <dt className="col-sm-5">{__('dashboard.feedback_tickets.company')}</dt>
                            <dd className="col-sm-7">{ticket.company ?? <span className="text-muted">—</span>}</dd>
                            <dt className="col-sm-5">{__('dashboard.feedback_tickets.order')}</dt>
                            <dd className="col-sm-7">
                                {ticket.order ? <span dir="ltr">{ticket.order.order_number}</span> : <span className="text-muted">—</span>}
                            </dd>
                            <dt className="col-sm-5">{__('dashboard.feedback_tickets.assigned_to')}</dt>
                            <dd className="col-sm-7">{assignedUser?.name ?? <span className="text-muted">{__('dashboard.feedback_tickets.not_assigned')}</span>}</dd>
                            {ticket.resolved_at ? (
                                <>
                                    <dt className="col-sm-5">{__('dashboard.feedback_tickets.resolved_at')}</dt>
                                    <dd className="col-sm-7">{formatDate(ticket.resolved_at, locale)}</dd>
                                </>
                            ) : null}
                        </dl>
                    </TableCard>

                    <TableCard title={__('dashboard.feedback_tickets.assignment')} variant="primary">
                        <form onSubmit={updateTicket}>
                            <div className="form-group">
                                <label htmlFor="feedback_status">{__('dashboard.feedback_tickets.col_status')}</label>
                                <select
                                    id="feedback_status"
                                    className="custom-select"
                                    value={ticketForm.data.status}
                                    onChange={(e) => ticketForm.setData('status', e.target.value)}
                                >
                                    {statusValues.map((value) => (
                                        <option key={value} value={value}>
                                            {statusLabels[value] ?? value}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="form-group">
                                <label htmlFor="feedback_priority">{__('dashboard.feedback_tickets.col_priority')}</label>
                                <select
                                    id="feedback_priority"
                                    className="custom-select"
                                    value={ticketForm.data.priority}
                                    onChange={(e) => ticketForm.setData('priority', e.target.value)}
                                >
                                    {priorityValues.map((value) => (
                                        <option key={value} value={value}>
                                            {priorityLabels[value] ?? value}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="form-group">
                                <label htmlFor="feedback_assigned_to">{__('dashboard.feedback_tickets.assigned_to')}</label>
                                <select
                                    id="feedback_assigned_to"
                                    className="custom-select"
                                    value={ticketForm.data.assigned_to_user_id}
                                    onChange={(e) => ticketForm.setData('assigned_to_user_id', e.target.value)}
                                >
                                    <option value="">{__('dashboard.feedback_tickets.not_assigned')}</option>
                                    {staffUsers.map((user) => (
                                        <option key={user.id} value={user.id}>
                                            {user.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <button type="submit" disabled={ticketForm.processing} className="btn btn-primary btn-block">
                                <i className={isRtl ? 'fas fa-save ml-1' : 'fas fa-save mr-1'} />
                                {__('dashboard.feedback_tickets.save_changes')}
                            </button>
                        </form>
                    </TableCard>
                </div>
            </div>
        </PlatformAdminLayout>
    );
}
