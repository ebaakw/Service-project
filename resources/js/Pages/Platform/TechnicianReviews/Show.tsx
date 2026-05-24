import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import StatusBadge from '@/Components/ui/StatusBadge';
import TableCard from '@/Components/ui/TableCard';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import RejectTechnicianModal from './RejectTechnicianModal';

type ServiceCategory = { id: number; name_ar: string; name_en: string | null };

type TechnicianDetail = {
    id: number;
    employee_code: string | null;
    display_name_ar: string | null;
    display_name_en: string | null;
    bio_ar: string | null;
    bio_en: string | null;
    job_title_ar: string | null;
    job_title_en: string | null;
    review_status: string | null;
    reviewed_at: string | null;
    rejection_reason: string | null;
    reviewed_by: { id: number; name: string; email: string | null } | null;
    company: { id: number; name: string; slug: string | null };
    user: { id: number; name: string; email: string | null; phone: string | null; status: string | null; avatar_url: string | null };
    service_categories: ServiceCategory[];
    can: { approve: boolean; reject: boolean };
};

export default function TechnicianReviewsShow({ technician }: { technician: TechnicianDetail }) {
    const page = usePage<PageProps>().props;
    const { direction } = page;
    const isRtl = direction === 'rtl';
    const statusLabels = page.enumLabels?.technician_review_status ?? {};
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [isRejecting, setIsRejecting] = useState(false);

    const statusBadge = (status: string | null) => {
        const s = (status ?? '').toLowerCase();
        if (s === 'approved') return <StatusBadge variant="success">{statusLabels.approved ?? __('dashboard.technician_reviews.approve')}</StatusBadge>;
        if (s === 'pending') return <StatusBadge variant="warning">{statusLabels.pending ?? __('dashboard.nav.pending_technicians')}</StatusBadge>;
        if (s === 'rejected') return <StatusBadge variant="danger">{statusLabels.rejected ?? __('dashboard.technician_reviews.reject')}</StatusBadge>;
        return <span className="text-muted">—</span>;
    };

    const submitRejection = (reason: string) => {
        router.post(
            route('platform.technician-reviews.reject', technician.id),
            { rejection_reason: reason },
            {
                preserveScroll: true,
                onStart: () => setIsRejecting(true),
                onSuccess: () => setShowRejectModal(false),
                onFinish: () => setIsRejecting(false),
            },
        );
    };

    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.technician_reviews"
            header={<h4 className="m-0 font-weight-bold text-dark">{__('dashboard.technician_reviews.title')}</h4>}
        >
            <Head title={__('dashboard.technician_reviews.title')} />

            <div className="mb-3 d-flex align-items-center justify-content-between">
                <Link href={route('platform.technician-reviews.index')} className="btn btn-outline-secondary btn-sm">
                    <i className={isRtl ? 'fas fa-arrow-right ms-1' : 'fas fa-arrow-left me-1'} />
                    {__('tenant.technicians.back')}
                </Link>
                <div className="btn-group btn-group-sm">
                    {technician.can?.approve ? (
                        <button type="button" className="btn btn-success" onClick={() => router.post(route('platform.technician-reviews.approve', technician.id))}>
                            <i className="fas fa-check mr-1" />
                            {__('dashboard.technician_reviews.approve')}
                        </button>
                    ) : null}
                    {technician.can?.reject ? (
                        <button
                            type="button"
                            className="btn btn-danger"
                            onClick={() => setShowRejectModal(true)}
                        >
                            <i className="fas fa-times mr-1" />
                            {__('dashboard.technician_reviews.reject')}
                        </button>
                    ) : null}
                </div>
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
                                    aria-label={__('tenant.technicians.avatar')}
                                >
                                    <i className="fas fa-user" />
                                </div>
                            )}
                            <div className="mx-3">
                                <div className="h5 mb-1">{technician.display_name_ar ?? technician.display_name_en ?? technician.user.name}</div>
                                <div className="text-muted small">{technician.job_title_ar ?? technician.job_title_en ?? '—'}</div>
                                <div className="text-muted small" dir="ltr">
                                    {technician.user.email ?? '—'}
                                </div>
                                <div className="text-muted small" dir="ltr">
                                    {technician.user.phone ?? '—'}
                                </div>
                                <div className="text-muted small" dir="ltr">
                                    {technician.user.status ?? '—'}
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
                    <TableCard title={__('dashboard.technician_reviews.title')}>
                        <div className="row">
                            <div className="col-md-6">
                                <div className="text-muted small">{__('tenant.technicians.review_status')}</div>
                                <div className="mb-3">{statusBadge(technician.review_status)}</div>
                            </div>
                            <div className="col-md-6">
                                <div className="text-muted small">{__('dashboard.technician_reviews.rejection_reason')}</div>
                                <div className="mb-3">{technician.rejection_reason ?? '—'}</div>
                            </div>
                            <div className="col-md-6">
                                <div className="text-muted small">{__('dashboard.technician_reviews.reviewed_at')}</div>
                                <div className="mb-3" dir="ltr">
                                    {technician.reviewed_at ?? '—'}
                                </div>
                            </div>
                            <div className="col-md-6">
                                <div className="text-muted small">{__('dashboard.technician_reviews.reviewed_by')}</div>
                                <div className="mb-3">{technician.reviewed_by?.name ?? '—'}</div>
                            </div>
                            <div className="col-md-6">
                                <div className="text-muted small">{__('tenant.technicians.employeeCode')}</div>
                                <div className="mb-3" dir="ltr">
                                    {technician.employee_code ?? '—'}
                                </div>
                            </div>
                        </div>
                    </TableCard>

                    <TableCard title={__('tenant.technicians.profileSection')}>
                        <div className="row">
                            <div className="col-md-6">
                                <div className="text-muted small">{__('tenant.technicians.jobTitle')}</div>
                                <div className="mb-3">{technician.job_title_ar ?? technician.job_title_en ?? '—'}</div>
                            </div>
                            <div className="col-md-6">
                                <div className="text-muted small">{__('tenant.technicians.bio')}</div>
                                <div className="mb-3">{technician.bio_ar ?? technician.bio_en ?? '—'}</div>
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

            <RejectTechnicianModal
                show={showRejectModal}
                technicianName={technician.display_name_ar ?? technician.display_name_en ?? technician.user.name}
                processing={isRejecting}
                onClose={() => {
                    if (!isRejecting) setShowRejectModal(false);
                }}
                onConfirm={submitRejection}
            />
        </PlatformAdminLayout>
    );
}

