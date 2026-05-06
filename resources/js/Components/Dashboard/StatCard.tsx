import classNames from 'classnames';
import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

export type StatCardProps = {
    label: string;
    value: string | number;
    /** Font Awesome class name, e.g. "fa-shopping-bag" */
    icon: string;
    /** AdminLTE/Bootstrap background utility class, e.g. "bg-warning" */
    color: string;
    href?: string;
    isRtl?: boolean;
    /** Optional additional text class, e.g. "text-dark" (defaults to white on colored backgrounds) */
    textColor?: string;
};

function inferIsRtl(): boolean {
    if (typeof document === 'undefined') return false;
    return document.documentElement?.getAttribute('dir') === 'rtl';
}

export default function StatCard({
    label,
    value,
    icon,
    color,
    href,
    isRtl,
    textColor,
}: StatCardProps) {
    const rtl = typeof isRtl === 'boolean' ? isRtl : inferIsRtl();

    const body: ReactNode = (
        <div
            className={classNames('service-stat-card', color, {
                'service-stat-card--rtl': rtl,
                'service-stat-card--ltr': !rtl,
            })}
        >
            <div className={classNames('service-stat-card__content', textColor ?? 'text-white')}>
                <div className="service-stat-card__value">{value}</div>
                <div className="service-stat-card__label">{label}</div>
            </div>
            <div className="service-stat-card__icon" aria-hidden="true">
                <i className={classNames('fas', icon)} />
            </div>
        </div>
    );

    if (href) {
        return (
            <Link href={href} className="service-stat-card__link">
                {body}
            </Link>
        );
    }

    return body;
}

