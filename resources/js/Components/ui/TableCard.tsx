import type { ReactNode } from 'react';
import classNames from 'classnames';

export default function TableCard({
    title,
    actions,
    actionsVariant = 'inline',
    children,
    className,
    headerClassName,
    titleClassName,
    actionsClassName,
    bodyClassName,
    variant = 'primary',
    bodyPadding = true,
}: {
    title?: ReactNode;
    actions?: ReactNode;
    actionsVariant?: 'inline' | 'card-tools';
    children: ReactNode;
    className?: string;
    headerClassName?: string;
    titleClassName?: string;
    actionsClassName?: string;
    bodyClassName?: string;
    variant?: 'primary' | 'secondary' | 'info' | 'success' | 'warning' | 'danger';
    /** AdminLTE tables often use `card-body p-0` */
    bodyPadding?: boolean;
}) {
    const headerBaseClass =
        actionsVariant === 'card-tools'
            ? 'card-header'
            : 'card-header d-flex align-items-center justify-content-between flex-wrap gap-2';

    return (
        <div className={classNames(`card card-outline card-${variant}`, className)}>
            {title || actions ? (
                <div
                    className={classNames(
                        headerBaseClass,
                        headerClassName,
                    )}
                >
                    {title ? <h3 className={classNames('card-title mb-0', titleClassName)}>{title}</h3> : <span />}
                    {actions ? (
                        <div
                            className={classNames(
                                actionsVariant === 'card-tools'
                                    ? 'card-tools'
                                    : 'd-flex align-items-center gap-2 flex-wrap',
                                actionsClassName,
                            )}
                        >
                            {actions}
                        </div>
                    ) : null}
                </div>
            ) : null}
            <div className={classNames('card-body', bodyPadding ? undefined : 'p-0', bodyClassName)}>{children}</div>
        </div>
    );
}

