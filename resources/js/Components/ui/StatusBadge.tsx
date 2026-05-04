import classNames from 'classnames';
import type { ReactNode } from 'react';

type Variant = 'secondary' | 'success' | 'info' | 'warning' | 'danger' | 'primary' | 'light';

export default function StatusBadge({
    children,
    variant = 'secondary',
    pill = false,
    className,
}: {
    children: ReactNode;
    variant?: Variant;
    pill?: boolean;
    className?: string;
}) {
    return (
        <span
            className={classNames(
                'badge',
                `bg-${variant}`,
                pill ? 'rounded-pill' : undefined,
                variant === 'light' ? 'text-muted' : undefined,
                className,
            )}
        >
            {children}
        </span>
    );
}

