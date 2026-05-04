import type { ReactNode } from 'react';
import classNames from 'classnames';

export default function TableCard({
    title,
    actions,
    children,
    className,
    headerClassName,
    bodyClassName,
    variant = 'primary',
    bodyPadding = true,
}: {
    title?: ReactNode;
    actions?: ReactNode;
    children: ReactNode;
    className?: string;
    headerClassName?: string;
    bodyClassName?: string;
    variant?: 'primary' | 'secondary' | 'info' | 'success' | 'warning' | 'danger';
    /** AdminLTE tables often use `card-body p-0` */
    bodyPadding?: boolean;
}) {
    return (
        <div className={classNames(`card card-outline card-${variant}`, className)}>
            {title || actions ? (
                <div
                    className={classNames(
                        'card-header d-flex align-items-center justify-content-between flex-wrap gap-2',
                        headerClassName,
                    )}
                >
                    {title ? <h3 className="card-title mb-0">{title}</h3> : <span />}
                    {actions ? <div className="d-flex align-items-center gap-2 flex-wrap">{actions}</div> : null}
                </div>
            ) : null}
            <div className={classNames('card-body', bodyPadding ? undefined : 'p-0', bodyClassName)}>{children}</div>
        </div>
    );
}

