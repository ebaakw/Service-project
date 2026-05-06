import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import DataTable from '@/Components/ui/DataTable';
import TableCard from '@/Components/ui/TableCard';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

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
    const { direction } = usePage<PageProps>().props;
    const isRtl = direction === 'rtl';

    return (
        <PlatformAdminLayout header={<h4 className="m-0 font-weight-bold text-dark">{__('tenant.customers.title')}</h4>}>
            <Head title={__('tenant.customers.title')} />

            <div className="mb-3">
                <Link href={route('platform.customers.index')} className="btn btn-default btn-sm">
                    <i className={isRtl ? 'fas fa-arrow-right ms-1' : 'fas fa-arrow-left me-1'} />
                    {__('tenant.customers.backToList')}
                </Link>
            </div>

            <div className="card">
                <div className="card-header">
                    <h3 className="card-title">{__('tenant.customers.profile')}</h3>
                </div>
                <div className="card-body">
                    <div className="row">
                        <div className="col-sm-12 col-md-6">
                            <div className="mb-3">
                                <div className="text-muted small">{__('tenant.customers.name')}</div>
                                <div>
                                    <strong>{customer.user.name}</strong>
                                </div>
                            </div>
                        </div>

                        <div className="col-sm-12 col-md-6">
                            <div className="mb-3">
                                <div className="text-muted small">{__('tenant.customers.email')}</div>
                                <div dir="ltr">{customer.user.email ?? '—'}</div>
                            </div>
                        </div>

                        <div className="col-sm-12 col-md-6">
                            <div className="mb-3">
                                <div className="text-muted small">{__('tenant.customers.phone')}</div>
                                <div dir="ltr">{customer.user.phone ?? '—'}</div>
                            </div>
                        </div>

                        <div className="col-sm-12 col-md-6">
                            <div className="mb-3">
                                <div className="text-muted small">{__('tenant.customers.dateOfBirth')}</div>
                                <div>{customer.date_of_birth ?? '—'}</div>
                            </div>
                        </div>

                        <div className="col-sm-12 col-md-6">
                            <div className="mb-3">
                                <div className="text-muted small">{__('tenant.customers.gender')}</div>
                                <div>{customer.gender ?? '—'}</div>
                            </div>
                        </div>

                        <div className="col-sm-12">
                            <div className="mb-0">
                                <div className="text-muted small">{__('tenant.customers.notes')}</div>
                                <div>{customer.notes ?? '—'}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <TableCard title={__('tenant.customers.addresses')} bodyPadding={false}>
                <DataTable<AddressRow>
                    columns={[
                        { key: 'label', header: __('tenant.customers.addressLabel'), render: (a) => a.label ?? '—' },
                        {
                            key: 'contact_phone',
                            header: __('tenant.customers.addressContactPhone'),
                            render: (a) => <span dir="ltr">{a.contact_phone ?? '—'}</span>,
                        },
                        { key: 'full_address', header: __('tenant.customers.fullAddress'), render: (a) => a.full_address ?? '—' },
                        { key: 'lat', header: __('tenant.customers.lat'), render: (a) => <span dir="ltr">{a.lat}</span> },
                        { key: 'lng', header: __('tenant.customers.lng'), render: (a) => <span dir="ltr">{a.lng}</span> },
                        {
                            key: 'is_default',
                            header: __('tenant.customers.isDefault'),
                            render: (a) => (a.is_default ? __('tenant.yes') : __('tenant.no')),
                        },
                    ]}
                    rows={customer.addresses}
                    rowKey={(a) => a.id}
                    emptyTitle={__('tenant.customers.addressesEmpty')}
                />
            </TableCard>
        </PlatformAdminLayout>
    );
}
