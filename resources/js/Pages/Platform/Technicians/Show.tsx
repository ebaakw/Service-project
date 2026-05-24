import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import StatusBadge from '@/Components/ui/StatusBadge';
import TableCard from '@/Components/ui/TableCard';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';

type CompanyRow = { id: number; name: string; slug: string };

type ServiceCategory = { id: number; name_ar: string; name_en: string | null };

type TechnicianDetail = {
    id: number;
    employee_code: string | null;
    bio: string | null;
    job_title: string | null;
    availability_status: string | null;
    availability_status_label?: string | null;
    is_active: boolean;
    company: CompanyRow;
    user: { name: string; email: string | null; phone: string | null; avatar_url: string | null };
    service_categories: ServiceCategory[];
    can_update?: boolean;
};

export default function PlatformTechniciansShow({ technician }: { technician: TechnicianDetail }) {
    const { direction } = usePage<PageProps>().props;
    const isRtl = direction === 'rtl';

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.all_technicians"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('tenant.technicians.viewTitle')}</h4>}
        >
            <Head title={__('tenant.technicians.viewTitle')} />

            <div className="mb-3 d-flex align-items-center justify-content-between">
                <Link href={route('platform.technicians.index')} className="btn btn-outline-secondary btn-sm">
                    <i className={isRtl ? 'fas fa-arrow-right ms-1' : 'fas fa-arrow-left me-1'} />
                    {__('tenant.technicians.back')}
                </Link>
                {technician.can_update ? (
                    <Link href={route('platform.technicians.edit', technician.id)} className="btn btn-primary btn-sm">
                        <i className="fas fa-edit mr-1" />
                        {__('tenant.technicians.edit')}
                    </Link>
                ) : null}
            </div>

            <div className="row">
                <div className="col-lg-4">
                    <TableCard title={__('tenant.technicians.userSection')}>
                        <div className="d-flex align-items-center">
                            {technician.user.avatar_url ? (
                                <img
                                    src={technician.user.avatar_url}
                                    alt={technician.user.name}
                                    className="rounded border bg-white"
                                    style={{ width: 80, height: 80, objectFit: 'cover' }}
                                />
                            ) : (
                                <div
                                    className="rounded border bg-light d-flex align-items-center justify-content-center text-muted"
                                    style={{ width: 80, height: 80 }}
                                >
                                    <i className="fas fa-user" />
                                </div>
                            )}
                            <div className="mx-3">
                                <div className="h5 mb-1">{technician.user.name}</div>
                                <div className="text-muted small" dir="ltr">
                                    {technician.user.email ?? '—'}
                                </div>
                                <div className="text-muted small" dir="ltr">
                                    {technician.user.phone ?? '—'}
                                </div>
                            </div>
                        </div>
                    </TableCard>

                    <TableCard title={__('tenant.technicians.companySection')}>
                        <div className="mb-1">
                            <strong>{technician.company.name}</strong>
                        </div>
                        <div className="text-muted small" dir="ltr">
                            {technician.company.slug ?? '—'}
                        </div>
                    </TableCard>
                </div>

                <div className="col-lg-8">
                    <TableCard title={__('tenant.technicians.profileSection')}>
                        <div className="row">
                            <div className="col-md-6">
                                <div className="text-muted small">{__('tenant.technicians.jobTitle')}</div>
                                <div className="mb-3">{technician.job_title ?? '—'}</div>
                            </div>
                            <div className="col-md-6">
                                <div className="text-muted small">{__('tenant.technicians.employeeCode')}</div>
                                <div className="mb-3" dir="ltr">
                                    {technician.employee_code ?? '—'}
                                </div>
                            </div>
                            <div className="col-md-6">
                                <div className="text-muted small">{__('tenant.technicians.availability')}</div>
                                <div className="mb-3">{technician.availability_status_label ?? technician.availability_status ?? '—'}</div>
                            </div>
                            <div className="col-md-6">
                                <div className="text-muted small">{__('tenant.technicians.active')}</div>
                                <div className="mb-3">
                                    {technician.is_active ? (
                                        <StatusBadge variant="success">{__('tenant.yes')}</StatusBadge>
                                    ) : (
                                        <StatusBadge variant="secondary">{__('tenant.no')}</StatusBadge>
                                    )}
                                </div>
                            </div>
                            <div className="col-12">
                                <div className="text-muted small">{__('tenant.technicians.bio')}</div>
                                <div>{technician.bio ?? '—'}</div>
                            </div>
                        </div>
                    </TableCard>

                    <TableCard title={__('tenant.technicians.categories')}>
                        {technician.service_categories.length === 0 ? (
                            <div className="text-muted">{__('tenant.technicians.noCategories')}</div>
                        ) : (
                            <ul className="mb-0 pl-3">
                                {technician.service_categories.map((c) => (
                                    <li key={c.id}>
                                        {c.name_ar}
                                        {c.name_en ? <span className="text-muted"> ({c.name_en})</span> : null}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </TableCard>
                </div>
            </div>
        </PlatformAdminLayout>
    );
}
