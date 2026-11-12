import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useMemo } from 'react';

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
        service_category: { name_ar: string } | null;
        customer: { user: { name: string; email: string | null } };
        technician: { id: number; user: { name: string } } | null;
        items: {
            id: number;
            name: string;
            quantity: string;
            unit_price: string;
            total_price: string;
        }[];
        payments: { id: number; payment_type: string; payment_status: string; amount: string; payment_url: string | null }[];
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
    technicians: { id: number; user: { name: string } }[];
};

export default function CompanyOrderShow({ order, inspectionPayment, technicians }: OrderPageProps) {
    const flash = usePage().props.flash as { message?: string } | undefined;

    const statusForm = useForm({
        status: order.status,
        note: '',
        cancellation_reason: '',
    });

    const assignForm = useForm({
        technician_id: '',
    });

    const itemForm = useForm({
        name: '',
        quantity: '1',
        unit_price: '',
        description: '',
    });

    const invoiceForm = useForm({
        force_empty: false as boolean,
    });

    const forceCompleteForm = useForm({
        note: '',
    });

    const submitStatus = (e: FormEvent) => {
        e.preventDefault();
        statusForm.patch(route('company.orders.update-status', order.id));
    };

    const submitAssign = (e: FormEvent) => {
        e.preventDefault();
        assignForm.post(route('company.orders.assign-technician', order.id));
    };

    const submitItem = (e: FormEvent) => {
        e.preventDefault();
        itemForm.post(route('company.orders.items.store', order.id), {
            onSuccess: () => itemForm.reset('name', 'unit_price', 'description'),
        });
    };

    const submitInvoice = (e: FormEvent) => {
        e.preventDefault();
        invoiceForm.post(route('company.orders.send-invoice', order.id));
    };

    const submitForceComplete = (e: FormEvent) => {
        e.preventDefault();
        forceCompleteForm.post(route('company.orders.force-complete', order.id));
    };

    const canAssign = order.status === 'searching_technician';

    const techHint = useMemo(() => technicians.map((t) => `${t.id} (${t.user.name})`).join(', '), [technicians]);

    return (
        <CompanyDashboardLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Order {order.order_number}</h2>}>
            <Head title={`Order ${order.order_number}`} />

            <div className="py-10">
                <div className="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                    {flash?.message && (
                        <div className="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{flash.message}</div>
                    )}

                    <div className="flex items-center justify-between">
                        <Link href={route('company.orders.index')} className="text-sm text-emerald-700 hover:text-emerald-900">
                            ← Orders
                        </Link>
                    </div>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <dl className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <dt className="text-xs uppercase text-gray-500">Status</dt>
                                <dd className="text-sm font-semibold text-gray-900">{order.status}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase text-gray-500">Category</dt>
                                <dd className="text-sm text-gray-900">{order.service_category?.name_ar ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase text-gray-500">Customer</dt>
                                <dd className="text-sm text-gray-900">{order.customer.user.name}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase text-gray-500">Technician</dt>
                                <dd className="text-sm text-gray-900">{order.technician?.user.name ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase text-gray-500">Queue position</dt>
                                <dd className="text-sm text-gray-900">{order.queue_position ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase text-gray-500">Estimated duration (min)</dt>
                                <dd className="text-sm text-gray-900">{order.estimated_duration_minutes ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase text-gray-500">Inspection fee</dt>
                                <dd className="text-sm text-gray-900">{order.inspection_fee}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase text-gray-500">Totals</dt>
                                <dd className="text-sm text-gray-900">
                                    Subtotal {order.subtotal} · Total {order.total_amount}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <h3 className="mb-3 text-sm font-semibold text-gray-900">Inspection payment</h3>
                        {inspectionPayment ? (
                            <div className="text-sm text-gray-700">
                                <div>Status: {inspectionPayment.payment_status}</div>
                                <div>Amount: {inspectionPayment.amount}</div>
                                {inspectionPayment.payment_url && (
                                    <div className="mt-2 truncate">
                                        Link: <span className="break-all">{inspectionPayment.payment_url}</span>
                                    </div>
                                )}
                            </div>
                        ) : (
                            <div className="text-sm text-gray-500">No inspection payment record.</div>
                        )}
                    </div>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <h3 className="mb-3 text-sm font-semibold text-gray-900">Update status</h3>
                        <form onSubmit={submitStatus} className="space-y-3">
                            <div>
                                <label className="block text-xs font-medium text-gray-700">Next status</label>
                                <input
                                    className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    value={statusForm.data.status}
                                    onChange={(e) => statusForm.setData('status', e.target.value)}
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-700">Note</label>
                                <input
                                    className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    value={statusForm.data.note}
                                    onChange={(e) => statusForm.setData('note', e.target.value)}
                                />
                            </div>
                            <button
                                type="submit"
                                className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-700"
                                disabled={statusForm.processing}
                            >
                                Save
                            </button>
                        </form>
                    </div>

                    {canAssign && (
                        <div className="rounded-lg bg-white p-6 shadow">
                            <h3 className="mb-3 text-sm font-semibold text-gray-900">Assign technician</h3>
                            <form onSubmit={submitAssign} className="space-y-3">
                                <div>
                                    <label className="block text-xs font-medium text-gray-700">Technician ID</label>
                                    <input
                                        className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                        value={assignForm.data.technician_id}
                                        onChange={(e) => assignForm.setData('technician_id', e.target.value)}
                                        placeholder="Technician primary key"
                                    />
                                    {technicians.length > 0 && (
                                        <p className="mt-2 text-xs text-gray-500">Available: {techHint}</p>
                                    )}
                                </div>
                                <button
                                    type="submit"
                                    className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-700"
                                    disabled={assignForm.processing}
                                >
                                    Assign
                                </button>
                            </form>
                        </div>
                    )}

                    <div className="rounded-lg bg-white p-6 shadow">
                        <h3 className="mb-3 text-sm font-semibold text-gray-900">Add item (started orders)</h3>
                        <form onSubmit={submitItem} className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <label className="block text-xs font-medium text-gray-700">Name</label>
                                <input
                                    className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    value={itemForm.data.name}
                                    onChange={(e) => itemForm.setData('name', e.target.value)}
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-700">Quantity</label>
                                <input
                                    className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    value={itemForm.data.quantity}
                                    onChange={(e) => itemForm.setData('quantity', e.target.value)}
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-700">Unit price</label>
                                <input
                                    className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    value={itemForm.data.unit_price}
                                    onChange={(e) => itemForm.setData('unit_price', e.target.value)}
                                />
                            </div>
                            <div className="sm:col-span-2">
                                <button
                                    type="submit"
                                    className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-700"
                                    disabled={itemForm.processing}
                                >
                                    Add item
                                </button>
                            </div>
                        </form>

                        <div className="mt-6">
                            <h4 className="text-xs font-semibold uppercase text-gray-500">Line items</h4>
                            <ul className="mt-2 divide-y divide-gray-100">
                                {order.items.map((i) => (
                                    <li key={i.id} className="flex items-center justify-between py-2 text-sm">
                                        <span>
                                            {i.name} · qty {i.quantity} · {i.total_price}
                                        </span>
                                        <button
                                            type="button"
                                            className="text-red-600 hover:text-red-800"
                                            onClick={() =>
                                                router.delete(route('company.orders.items.destroy', [order.id, i.id]))
                                            }
                                        >
                                            Remove
                                        </button>
                                    </li>
                                ))}
                                {order.items.length === 0 && <li className="py-2 text-sm text-gray-500">No items yet.</li>}
                            </ul>
                        </div>
                    </div>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <h3 className="mb-3 text-sm font-semibold text-gray-900">Final invoice</h3>
                        <form onSubmit={submitInvoice} className="space-y-3">
                            <label className="flex items-center gap-2 text-sm text-gray-700">
                                <input
                                    type="checkbox"
                                    checked={invoiceForm.data.force_empty}
                                    onChange={(e) => invoiceForm.setData('force_empty', e.target.checked)}
                                />
                                Force empty invoice (requires company setting / permission)
                            </label>
                            <button
                                type="submit"
                                className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-700"
                                disabled={invoiceForm.processing}
                            >
                                Generate & send payment link
                            </button>
                        </form>

                        <div className="mt-4 text-sm text-gray-700">
                            <div className="font-semibold">Invoices</div>
                            <ul className="mt-2 space-y-2">
                                {order.invoices.map((inv) => (
                                    <li key={inv.id}>
                                        {inv.invoice_number} · {inv.status}
                                        {inv.payment_link && (
                                            <div className="break-all text-xs text-gray-600">{inv.payment_link}</div>
                                        )}
                                    </li>
                                ))}
                                {order.invoices.length === 0 && <li className="text-gray-500">No invoices yet.</li>}
                            </ul>
                        </div>
                    </div>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <h3 className="mb-3 text-sm font-semibold text-gray-900">Force complete</h3>
                        <form onSubmit={submitForceComplete} className="space-y-3">
                            <div>
                                <label className="block text-xs font-medium text-gray-700">Note</label>
                                <input
                                    className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    value={forceCompleteForm.data.note}
                                    onChange={(e) => forceCompleteForm.setData('note', e.target.value)}
                                />
                            </div>
                            <button
                                type="submit"
                                className="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-gray-800"
                                disabled={forceCompleteForm.processing}
                            >
                                Mark completed (authorized)
                            </button>
                        </form>
                    </div>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <h3 className="mb-3 text-sm font-semibold text-gray-900">Payments</h3>
                        <ul className="space-y-2 text-sm text-gray-700">
                            {order.payments.map((p) => (
                                <li key={p.id}>
                                    {p.payment_type} · {p.payment_status} · {p.amount}
                                    {p.payment_url && <div className="break-all text-xs text-gray-600">{p.payment_url}</div>}
                                </li>
                            ))}
                            {order.payments.length === 0 && <li className="text-gray-500">No payments yet.</li>}
                        </ul>
                    </div>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <h3 className="mb-3 text-sm font-semibold text-gray-900">Status history</h3>
                        <ul className="space-y-2 text-sm text-gray-700">
                            {order.status_histories.map((h) => (
                                <li key={h.id}>
                                    <span className="font-mono text-xs text-gray-500">{h.created_at}</span>:{' '}
                                    {h.old_status ?? '∅'} → {h.new_status}
                                    {h.note ? ` · ${h.note}` : ''}
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </div>
        </CompanyDashboardLayout>
    );
}
