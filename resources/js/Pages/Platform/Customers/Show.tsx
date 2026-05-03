import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { Head, Link } from '@inertiajs/react';

type AddressRow = {
    id: number;
    label: string | null;
    contact_name: string | null;
    contact_phone: string | null;
    full_address: string | null;
    lat: string;
    lng: string;
    is_default: boolean;
};

type CustomerDetail = {
    id: number;
    date_of_birth: string | null;
    gender: string | null;
    notes: string | null;
    default_address_id: number | null;
    user: { name: string; email: string | null; phone: string | null };
    addresses: AddressRow[];
};

export default function PlatformCustomersShow({ customer }: { customer: CustomerDetail }) {
    return (
        <PlatformAdminLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Customer</h2>}>
            <Head title="Customer" />

            <div className="py-10">
                <div className="mx-auto max-w-5xl space-y-8 sm:px-6 lg:px-8">
                    <div>
                        <Link href={route('platform.customers.index')} className="text-sm text-indigo-600 hover:text-indigo-800">
                            ← Customers
                        </Link>
                    </div>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <h3 className="text-lg font-semibold text-gray-900">Profile</h3>
                        <dl className="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt className="text-xs font-medium uppercase text-gray-500">Name</dt>
                                <dd className="text-sm text-gray-900">{customer.user.name}</dd>
                            </div>
                            <div>
                                <dt className="text-xs font-medium uppercase text-gray-500">Email</dt>
                                <dd className="text-sm text-gray-900">{customer.user.email ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs font-medium uppercase text-gray-500">Phone</dt>
                                <dd className="text-sm text-gray-900">{customer.user.phone ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs font-medium uppercase text-gray-500">Date of birth</dt>
                                <dd className="text-sm text-gray-900">{customer.date_of_birth ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs font-medium uppercase text-gray-500">Gender</dt>
                                <dd className="text-sm text-gray-900">{customer.gender ?? '—'}</dd>
                            </div>
                            <div className="sm:col-span-2">
                                <dt className="text-xs font-medium uppercase text-gray-500">Notes</dt>
                                <dd className="text-sm text-gray-900">{customer.notes ?? '—'}</dd>
                            </div>
                        </dl>
                    </div>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <h3 className="text-lg font-semibold text-gray-900">Addresses</h3>
                        <div className="mt-4 overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-3 py-2 text-start text-xs font-medium uppercase text-gray-500">Label</th>
                                        <th className="px-3 py-2 text-start text-xs font-medium uppercase text-gray-500">Address</th>
                                        <th className="px-3 py-2 text-start text-xs font-medium uppercase text-gray-500">Lat</th>
                                        <th className="px-3 py-2 text-start text-xs font-medium uppercase text-gray-500">Lng</th>
                                        <th className="px-3 py-2 text-start text-xs font-medium uppercase text-gray-500">Default</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 bg-white">
                                    {customer.addresses.length === 0 && (
                                        <tr>
                                            <td colSpan={5} className="px-3 py-6 text-center text-sm text-gray-500">
                                                No addresses.
                                            </td>
                                        </tr>
                                    )}
                                    {customer.addresses.map((a) => (
                                        <tr key={a.id}>
                                            <td className="whitespace-nowrap px-3 py-2 text-sm text-gray-900">{a.label ?? '—'}</td>
                                            <td className="max-w-md px-3 py-2 text-sm text-gray-700">{a.full_address ?? '—'}</td>
                                            <td className="whitespace-nowrap px-3 py-2 text-sm text-gray-700">{a.lat}</td>
                                            <td className="whitespace-nowrap px-3 py-2 text-sm text-gray-700">{a.lng}</td>
                                            <td className="whitespace-nowrap px-3 py-2 text-sm text-gray-700">{a.is_default ? 'Yes' : 'No'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </PlatformAdminLayout>
    );
}
