import { Link, usePage } from '@inertiajs/react';
import classNames from 'classnames';

type LinkItem = { url: string | null; label: string; active: boolean };
type PaginationData = {
    current_page?: number;
    last_page?: number;
    prev_page_url?: string | null;
    next_page_url?: string | null;
    from?: number | null;
    to?: number | null;
    total?: number | null;
};

function paginationState(links: LinkItem[], data?: PaginationData) {
    if (data?.current_page != null && data?.last_page != null) {
        const last = Math.max(1, Number(data.last_page) || 1);
        const current = Math.min(last, Math.max(1, Number(data.current_page) || 1));
        return {
            current,
            last,
            prevUrl: data.prev_page_url ?? null,
            nextUrl: data.next_page_url ?? null,
        };
    }
    if (!Array.isArray(links) || links.length < 3) return null;
    const prev = links[0];
    const next = links[links.length - 1];
    const pageLinks = links.slice(1, -1);
    const active = pageLinks.find((l) => l.active);
    let current = 1;
    if (active?.label != null) {
        const n = parseInt(String(active.label).replace(/\D/g, ''), 10);
        current = Number.isFinite(n) && n > 0 ? n : pageLinks.indexOf(active) + 1;
    }
    const last = pageLinks.length > 0 ? pageLinks.length : 1;
    return {
        current,
        last,
        prevUrl: prev?.url ?? null,
        nextUrl: next?.url ?? null,
    };
}

function showingText(locale: string, data?: PaginationData) {
    if (!data) return null;
    if (data.total == null && data.from == null && data.to == null) return null;
    const total = Number(data.total ?? 0);
    const from = total === 0 ? 0 : Number(data.from ?? 0);
    const to = total === 0 ? 0 : Number(data.to ?? 0);
    if (!Number.isFinite(total) || !Number.isFinite(from) || !Number.isFinite(to)) return null;
    return locale === 'ar'
        ? `عرض ${from} إلى ${to} من ${total}`
        : `Showing ${from} to ${to} of ${total}`;
}

export default function Pagination({
    links = [],
    data,
    className,
    labels,
}: {
    links: LinkItem[];
    data?: PaginationData;
    className?: string;
    labels?: { prev?: string; next?: string };
}) {
    const locale = (usePage().props as { locale?: string }).locale ?? 'en';
    const state = paginationState(links, data);
    if (!state) return null;

    const prevLabel = labels?.prev ?? (locale === 'ar' ? 'السابق' : 'Prev');
    const nextLabel = labels?.next ?? (locale === 'ar' ? 'التالي' : 'Next');
    const txt = showingText(locale, data);

    return (
        <div
            className={classNames(
                'd-flex justify-content-between align-items-center flex-wrap gap-2',
                className ?? 'mt-2',
            )}
        >
            <span className="text-muted small">{txt ?? '\u00A0'}</span>
            <div className="d-flex align-items-center gap-2 flex-wrap">
                {state.prevUrl ? (
                    <Link href={state.prevUrl} prefetch className="btn btn-outline-secondary btn-sm">
                        {prevLabel}
                    </Link>
                ) : (
                    <span className="btn btn-outline-secondary btn-sm disabled" aria-disabled="true">
                        {prevLabel}
                    </span>
                )}
                <span className="text-muted px-2 small" dir="ltr">
                    {state.current} / {state.last}
                </span>
                {state.nextUrl ? (
                    <Link href={state.nextUrl} prefetch className="btn btn-outline-secondary btn-sm">
                        {nextLabel}
                    </Link>
                ) : (
                    <span className="btn btn-outline-secondary btn-sm disabled" aria-disabled="true">
                        {nextLabel}
                    </span>
                )}
            </div>
        </div>
    );
}

