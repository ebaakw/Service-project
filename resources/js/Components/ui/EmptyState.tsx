import type { ReactNode } from 'react';

export default function EmptyState({
    title,
    description,
    iconClassName = 'fas fa-inbox',
}: {
    title: ReactNode;
    description?: ReactNode;
    iconClassName?: string;
}) {
    return (
        <div className="p-4 text-center text-muted">
            <div className="mb-2" style={{ fontSize: 22, opacity: 0.7 }}>
                <i className={iconClassName} />
            </div>
            <div className="font-weight-bold">{title}</div>
            {description ? <div className="small mt-1">{description}</div> : null}
        </div>
    );
}

