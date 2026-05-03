import React, { useMemo } from "react";
import { Link } from "@inertiajs/react";
import classNames from "classnames";
import { __ } from "@/Utility/Hooks/useTranslate.jsx";

/**
 * Tenant pagination: previous / current÷last / next using Laravel paginator fields,
 * with Arabic (or locale) labels. Layout matches ReportDataTable (info + controls)
 * so controls align to the visual start/end consistently in RTL/LTR.
 */
function paginationState(links, data) {
    if (data != null && data.current_page != null && data.last_page != null) {
        const last = Math.max(1, Number(data.last_page) || 1);
        const current = Math.min(
            last,
            Math.max(1, Number(data.current_page) || 1),
        );
        return {
            current,
            last,
            prevUrl: data.prev_page_url ?? null,
            nextUrl: data.next_page_url ?? null,
        };
    }
    if (!Array.isArray(links) || links.length < 3) {
        return null;
    }
    const prev = links[0];
    const next = links[links.length - 1];
    const pageLinks = links.slice(1, -1);
    const active = pageLinks.find((l) => l.active);
    let current = 1;
    if (active?.label != null) {
        const n = parseInt(String(active.label).replace(/\D/g, ""), 10);
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

function showingToOfText(data) {
    if (data == null) {
        return null;
    }
    if (data.total == null && data.from == null && data.to == null) {
        return null;
    }
    const total = Number(data.total ?? 0);
    const from =
        total === 0
            ? 0
            : data.from != null
              ? Number(data.from)
              : 0;
    const to =
        total === 0
            ? 0
            : data.to != null
              ? Number(data.to)
              : 0;
    if (!Number.isFinite(total) || !Number.isFinite(from) || !Number.isFinite(to)) {
        return null;
    }
    return __("tenant.pagination_showing_to_of")
        .replace(":from", String(from))
        .replace(":to", String(to))
        .replace(":total", String(total));
}

export default function Pagination({ links = [], data, className }) {
    const state = useMemo(() => paginationState(links, data), [links, data]);

    if (!state) {
        return null;
    }

    const { current, last, prevUrl, nextUrl } = state;
    const showingText = showingToOfText(data);

    return (
        <div
            className={classNames(
                "d-flex justify-content-between align-items-center flex-wrap gap-2",
                className !== undefined ? className : "mt-2",
            )}
        >
            <span className="text-muted small">
                {showingText ?? "\u00a0"}
            </span>
            <div className="d-flex align-items-center gap-2 flex-wrap">
                {prevUrl ? (
                    <Link
                        href={prevUrl}
                        prefetch
                        className="btn btn-outline-secondary btn-sm"
                    >
                        {__("tenant.pagination_prev")}
                    </Link>
                ) : (
                    <span
                        className="btn btn-outline-secondary btn-sm disabled"
                        aria-disabled="true"
                    >
                        {__("tenant.pagination_prev")}
                    </span>
                )}
                <span className="text-muted px-2 small" dir="ltr">
                    {current} / {last}
                </span>
                {nextUrl ? (
                    <Link
                        href={nextUrl}
                        prefetch
                        className="btn btn-outline-secondary btn-sm"
                    >
                        {__("tenant.pagination_next")}
                    </Link>
                ) : (
                    <span
                        className="btn btn-outline-secondary btn-sm disabled"
                        aria-disabled="true"
                    >
                        {__("tenant.pagination_next")}
                    </span>
                )}
            </div>
        </div>
    );
}
