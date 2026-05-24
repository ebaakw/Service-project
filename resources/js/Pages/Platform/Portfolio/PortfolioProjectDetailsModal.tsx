import StatusBadge from '@/Components/ui/StatusBadge';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';
import type { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useEffect, useMemo, useState } from 'react';

export type PortfolioProjectImage = {
    id: number;
    url: string;
    sort_order: number;
    file_name: string | null;
    mime_type: string | null;
    size: number | null;
};

export type PortfolioProjectDetails = {
    id: number;
    title_ar: string;
    title_en: string | null;
    description_ar?: string | null;
    description_en?: string | null;
    location?: string | null;
    location_ar?: string | null;
    location_en?: string | null;
    completed_at?: string | null;
    is_featured?: boolean;
    is_active?: boolean;
    review_status?: string | null;
    reviewed_at?: string | null;
    rejection_reason?: string | null;
    created_at?: string | null;
    thumbnail_url?: string | null;
    images_count?: number;
    images?: PortfolioProjectImage[];
    company?: { id: number; name: string; slug?: string | null } | null;
    service_category?: { id: number; name_ar: string; name_en: string | null } | null;
    technicians?: { id: number; user: { name: string | null } }[];
    technician?: { id: number; user: { name: string | null } } | null;
    reviewed_by?: { id: number; name: string; email: string } | null;
};

type PortfolioProjectDetailsModalProps = {
    show: boolean;
    project: PortfolioProjectDetails | null;
    onClose: () => void;
};

function localizedPair(ar: string | null | undefined, en: string | null | undefined, locale: string): string {
    if (locale === 'en') {
        return en || ar || '—';
    }

    return ar || en || '—';
}

function formatDate(value: string | null | undefined, locale: string): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(locale);
}

function DetailRow({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="border-bottom py-2">
            <div className="text-muted small">{label}</div>
            <div className="font-weight-bold text-dark" style={{ whiteSpace: 'pre-wrap' }}>
                {children || '—'}
            </div>
        </div>
    );
}

export default function PortfolioProjectDetailsModal({ show, project, onClose }: PortfolioProjectDetailsModalProps) {
    const { locale, direction, enumLabels } = usePage<PageProps>().props;
    const isRtl = direction === 'rtl';
    const iconSpacingClass = isRtl ? 'ms-1' : 'me-1';
    const [selectedImageId, setSelectedImageId] = useState<number | null>(null);

    const images = useMemo(() => project?.images ?? [], [project]);
    const selectedImage = images.find((image) => image.id === selectedImageId) ?? images[0] ?? null;

    useEffect(() => {
        if (show) {
            setSelectedImageId(images[0]?.id ?? null);
        }
    }, [images, show]);

    if (!show || !project) {
        return null;
    }

    const title = localizedPair(project.title_ar, project.title_en, locale);
    const category = localizedPair(project.service_category?.name_ar, project.service_category?.name_en, locale);
    const location = localizedPair(project.location_ar ?? project.location, project.location_en ?? project.location, locale);
    const technicianNames = (project.technicians ?? [])
        .map((technician) => technician.user.name)
        .filter(Boolean)
        .join(', ');
    const reviewStatus = String(project.review_status ?? '').toLowerCase();
    const reviewStatusLabels = enumLabels?.portfolio_project_review_status ?? {};

    const reviewStatusBadge = () => {
        if (reviewStatus === 'approved') return <StatusBadge variant="success">{reviewStatusLabels.approved ?? reviewStatus}</StatusBadge>;
        if (reviewStatus === 'pending') return <StatusBadge variant="warning">{reviewStatusLabels.pending ?? reviewStatus}</StatusBadge>;
        if (reviewStatus === 'rejected') return <StatusBadge variant="danger">{reviewStatusLabels.rejected ?? reviewStatus}</StatusBadge>;

        return '—';
    };

    return (
        <>
            <div
                className="modal fade show d-block"
                tabIndex={-1}
                role="dialog"
                aria-modal="true"
                onMouseDown={(event) => {
                    if (event.target === event.currentTarget) {
                        onClose();
                    }
                }}
            >
                <div className="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
                    <div className="modal-content">
                        <div className="modal-header">
                            <div>
                                <h5 className="modal-title">{__('dashboard.portfolio_projects.details_title')}</h5>
                                <div className="text-muted small">{title}</div>
                            </div>
                            <button type="button" className="close m-0 p-0" aria-label={__('dashboard.actions.close')} onClick={onClose}>
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <div className="modal-body">
                            <div className="row">
                                <div className="col-lg-7 mb-4 mb-lg-0">
                                    <div className="card h-100 mb-0">
                                        <div className="card-header">
                                            <h3 className="card-title">{__('dashboard.portfolio_projects.image_gallery')}</h3>
                                            {selectedImage ? (
                                                <div className="card-tools">
                                                    <a
                                                        href={selectedImage.url}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="btn btn-tool"
                                                    >
                                                        <i className={`fas fa-external-link-alt ${iconSpacingClass}`} />
                                                        {__('dashboard.portfolio_projects.open_image')}
                                                    </a>
                                                </div>
                                            ) : null}
                                        </div>
                                        <div className="card-body">
                                            {selectedImage ? (
                                                <>
                                                    <div className="rounded border bg-light p-2 text-center">
                                                        <img
                                                            src={selectedImage.url}
                                                            alt={title}
                                                            className="img-fluid rounded"
                                                            style={{ maxHeight: 520, objectFit: 'contain' }}
                                                        />
                                                    </div>
                                                    <div className="mt-3 d-flex flex-wrap">
                                                        {images.map((image) => (
                                                            <button
                                                                key={image.id}
                                                                type="button"
                                                                className={`btn p-1 mx-1 mb-2 ${
                                                                    image.id === selectedImage.id ? 'btn-primary' : 'btn-outline-secondary'
                                                                }`}
                                                                onClick={() => setSelectedImageId(image.id)}
                                                                aria-label={__('dashboard.portfolio_projects.selected_image')}
                                                            >
                                                                <img
                                                                    src={image.url}
                                                                    alt={title}
                                                                    className="rounded bg-white"
                                                                    style={{ width: 72, height: 54, objectFit: 'cover' }}
                                                                />
                                                            </button>
                                                        ))}
                                                    </div>
                                                    <div className="text-muted small">
                                                        <div>
                                                            <strong>{__('dashboard.portfolio_projects.image_file_name')}:</strong>{' '}
                                                            {selectedImage.file_name ?? '—'}
                                                        </div>
                                                        <div>
                                                            <strong>{__('dashboard.portfolio_projects.image_mime_type')}:</strong>{' '}
                                                            {selectedImage.mime_type ?? '—'}
                                                        </div>
                                                    </div>
                                                </>
                                            ) : (
                                                <div className="py-5 text-center text-muted">
                                                    <i className="far fa-images fa-3x mb-2" />
                                                    <div>{__('dashboard.portfolio_projects.no_images_available')}</div>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                <div className="col-lg-5">
                                    <div className="card mb-0">
                                        <div className="card-header">
                                            <h3 className="card-title">{__('dashboard.portfolio_projects.project_details')}</h3>
                                        </div>
                                        <div className="card-body">
                                            <DetailRow label={__('dashboard.portfolio_projects.title_ar')}>{project.title_ar}</DetailRow>
                                            <DetailRow label={__('dashboard.portfolio_projects.title_en')}>{project.title_en}</DetailRow>
                                            <DetailRow label={__('dashboard.portfolio_projects.description_ar')}>
                                                {project.description_ar}
                                            </DetailRow>
                                            <DetailRow label={__('dashboard.portfolio_projects.description_en')}>
                                                {project.description_en}
                                            </DetailRow>
                                            <DetailRow label={__('dashboard.portfolio_projects.company')}>
                                                {project.company?.name ?? '—'}
                                                {project.company?.slug ? (
                                                    <div className="text-muted small" dir="ltr">
                                                        {project.company.slug}
                                                    </div>
                                                ) : null}
                                            </DetailRow>
                                            <DetailRow label={__('dashboard.portfolio_projects.service_category')}>{category}</DetailRow>
                                            <DetailRow label={__('dashboard.portfolio_projects.location')}>{location}</DetailRow>
                                            <DetailRow label={__('dashboard.portfolio_projects.completed_at')}>
                                                {project.completed_at ?? '—'}
                                            </DetailRow>
                                            <DetailRow label={__('dashboard.portfolio_projects.technicians')}>
                                                {technicianNames || project.technician?.user.name || __('dashboard.portfolio_projects.no_technician')}
                                            </DetailRow>
                                            <DetailRow label={__('dashboard.portfolio_reviews.review_status')}>{reviewStatusBadge()}</DetailRow>
                                            <DetailRow label={__('dashboard.portfolio_projects.active')}>
                                                <StatusBadge variant={project.is_active ? 'success' : 'secondary'}>
                                                    {project.is_active ? __('dashboard.status.active') : __('dashboard.status.inactive')}
                                                </StatusBadge>
                                            </DetailRow>
                                            <DetailRow label={__('dashboard.portfolio_projects.featured')}>
                                                {project.is_featured ? __('dashboard.common.yes') : __('dashboard.common.no')}
                                            </DetailRow>
                                            <DetailRow label={__('dashboard.portfolio_reviews.rejection_reason')}>
                                                {project.rejection_reason}
                                            </DetailRow>
                                            <DetailRow label={__('dashboard.portfolio_projects.created_at')}>
                                                {formatDate(project.created_at, locale)}
                                            </DetailRow>
                                            <DetailRow label={__('dashboard.portfolio_projects.reviewed_at')}>
                                                {formatDate(project.reviewed_at, locale)}
                                            </DetailRow>
                                            <DetailRow label={__('dashboard.portfolio_projects.reviewed_by')}>
                                                {project.reviewed_by?.name ?? '—'}
                                            </DetailRow>
                                            <DetailRow label={__('dashboard.portfolio_reviews.images')}>
                                                {__('dashboard.portfolio_projects.photos_count', {
                                                    count: String(project.images_count ?? images.length),
                                                })}
                                            </DetailRow>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="modal-footer">
                            <button type="button" className="btn btn-outline-secondary" onClick={onClose}>
                                {__('dashboard.actions.close')}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div className="modal-backdrop fade show" />
        </>
    );
}
