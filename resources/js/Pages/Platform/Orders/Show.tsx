import OrderChatPanel from '@/Components/Orders/OrderChatPanel';
import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import type { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

type OrderPageProps = {
    order: {
        id: number;
        order_number: string;
        status: string;
        queue_position?: number | null;
        estimated_duration_minutes?: number | null;
        inspection_fee: string;
        subtotal: string;
        total_amount: string;
        last_message_at?: string | null;
        last_message_preview?: string | null;
        company: { name: string; name_ar?: string | null; name_en?: string | null } | null;
        service_category: { name_ar: string; name_en?: string | null } | null;
        customer: { user: { name: string; email: string | null; phone?: string | null } };
        technician: { id: number; user: { name: string } } | null;
        items: {
            id: number;
            name: string;
            quantity: string;
            unit_price: string;
            total_price: string;
        }[];
        payments: {
            id: number;
            payment_type: string;
            payment_status: string;
            amount: string;
            payment_url: string | null;
        }[];
        invoices: { id: number; invoice_number: string; status: string; payment_link: string | null }[];
        status_histories: {
            id: number;
            old_status: string | null;
            new_status: string;
            note: string | null;
            created_at: string;
            changed_by: { name: string } | null;
        }[];
    };
    inspectionPayment: {
        id: number;
        payment_status: string;
        amount: string;
        payment_url: string | null;
    } | null;
    canReplyChat: boolean;
    messagesIndexUrl: string;
    messagesStoreUrl: string;
};

export default function PlatformOrderShow({
    order,
    inspectionPayment,
    canReplyChat,
    messagesIndexUrl,
    messagesStoreUrl,
}: OrderPageProps) {
    const page = usePage<PageProps>().props;
    const t = page.t?.orders ?? {};
    const statusLabels = page.enumLabels?.order_status ?? {};
    const locale = page.locale;

    const companyName =
        locale === 'en'
            ? order.company?.name_en || order.company?.name_ar || order.company?.name
            : order.company?.name_ar || order.company?.name_en || order.company?.name;

    return (
        <PlatformAdminLayout
            header={
                <h4 className="m-0 font-weight-bold text-dark">
                    {t.page_title ?? 'Orders'} · <span dir="ltr">{order.order_number}</span>
                </h4>
            }
        >
            <Head title={`${t.page_title ?? 'Order'} ${order.order_number}`} />

            <div className="mb-3">
                <Link href={route('platform.orders.index')} className="btn btn-outline-secondary btn-sm">
                    ← {t.page_title ?? 'Orders'}
                </Link>
            </div>

            <div className="row">
                <div className="col-lg-7">
                    <div className="card card-outline card-secondary mb-3">
                        <div className="card-header">
                            <h3 className="card-title mb-0">{order.order_number}</h3>
                        </div>
                        <div className="card-body">
                            <dl className="row mb-0">
                                <dt className="col-sm-4">{t.col_status ?? 'Status'}</dt>
                                <dd className="col-sm-8">{statusLabels[order.status] ?? order.status}</dd>

                                <dt className="col-sm-4">{t.col_company ?? 'Company'}</dt>
                                <dd className="col-sm-8">{companyName ?? '—'}</dd>

                                <dt className="col-sm-4">{t.col_customer ?? 'Customer'}</dt>
                                <dd className="col-sm-8">
                                    {order.customer.user.name}
                                    {order.customer.user.phone ? ` · ${order.customer.user.phone}` : ''}
                                </dd>

                                <dt className="col-sm-4">{t.col_technician ?? 'Technician'}</dt>
                                <dd className="col-sm-8">{order.technician?.user.name ?? '—'}</dd>

                                <dt className="col-sm-4">{t.col_service_category ?? 'Service'}</dt>
                                <dd className="col-sm-8">
                                    {locale === 'en'
                                        ? order.service_category?.name_en || order.service_category?.name_ar || '—'
                                        : order.service_category?.name_ar || order.service_category?.name_en || '—'}
                                </dd>

                                <dt className="col-sm-4">{t.col_queue_position ?? 'Queue #'}</dt>
                                <dd className="col-sm-8" dir="ltr">
                                    {order.queue_position ?? '—'}
                                </dd>

                                <dt className="col-sm-4">{t.col_collected_amount ?? 'Total'}</dt>
                                <dd className="col-sm-8" dir="ltr">
                                    {order.total_amount}
                                </dd>
                            </dl>
                        </div>
                    </div>

                    <div className="card card-outline card-secondary mb-3">
                        <div className="card-header">
                            <h3 className="card-title mb-0">{t.col_inspection_payment_status ?? 'Inspection payment'}</h3>
                        </div>
                        <div className="card-body">
                            {inspectionPayment ? (
                                <div>
                                    <div>
                                        {inspectionPayment.payment_status} · {inspectionPayment.amount}
                                    </div>
                                    {inspectionPayment.payment_url && (
                                        <div className="small text-muted text-break mt-1">{inspectionPayment.payment_url}</div>
                                    )}
                                </div>
                            ) : (
                                <div className="text-muted">—</div>
                            )}
                        </div>
                    </div>

                    <div className="card card-outline card-secondary mb-3">
                        <div className="card-header">
                            <h3 className="card-title mb-0">Timeline</h3>
                        </div>
                        <div className="card-body p-0">
                            <ul className="list-group list-group-flush">
                                {order.status_histories.map((h) => (
                                    <li key={h.id} className="list-group-item small">
                                        <span dir="ltr" className="text-muted">
                                            {h.created_at}
                                        </span>
                                        {': '}
                                        {h.old_status ?? '∅'} → {statusLabels[h.new_status] ?? h.new_status}
                                        {h.note ? ` · ${h.note}` : ''}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </div>

                <div className="col-lg-5">
                    <OrderChatPanel
                        indexUrl={messagesIndexUrl}
                        storeUrl={messagesStoreUrl}
                        canReplyChat={canReplyChat}
                    />
                </div>
            </div>
        </PlatformAdminLayout>
    );
}
