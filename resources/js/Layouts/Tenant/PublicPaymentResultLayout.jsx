import React, { useMemo } from "react";
import { Head, usePage } from "@inertiajs/react";
import { __ } from "@/Utility/Hooks/useTranslate.jsx";

function digitsOnly(phone) {
    return String(phone || "").replace(/\D/g, "");
}

/**
 * Minimal full-screen layout for client-facing payment result pages.
 * No tenant navbar, sidebar, notifications, or staff UI chrome.
 */
export default function PublicPaymentResultLayout({ headTitle, children }) {
    const { support_whatsapp_number, locale } = usePage().props || {};
    const isRtl = locale === "ar";

    const waHref = useMemo(() => {
        const d = digitsOnly(support_whatsapp_number);
        if (!d) return null;
        return `https://wa.me/${d}`;
    }, [support_whatsapp_number]);

    const displayPhone = useMemo(() => {
        const raw = String(support_whatsapp_number || "").trim();
        if (!raw) return "";
        const d = digitsOnly(raw);
        if (d.length < 6) return raw;
        return d;
    }, [support_whatsapp_number]);

    return (
        <div
            className="d-flex flex-column min-vh-100"
            dir={isRtl ? "rtl" : "ltr"}
            style={{ background: "#f4f6f9" }}
        >
            <Head title={headTitle} />

            <div className="flex-grow-1 d-flex align-items-center justify-content-center px-3 py-5">
                <div className="w-100" style={{ maxWidth: "420px" }}>
                    {children}
                </div>
            </div>

            <footer className="text-center px-3 pb-4 pt-0">
                <div
                    className="mx-auto mb-3"
                    style={{
                        maxWidth: "28rem",
                        fontSize: "0.88rem",
                        lineHeight: 1.55,
                        color: "#64748b",
                    }}
                >
                    <p className="mb-2">
                        {__("tenant.payment_result_footer_intro")}
                    </p>
                    <p className="mb-0">
                        {__("tenant.payment_result_footer_regards_brand")}
                    </p>
                </div>
                {waHref && displayPhone ? (
                    <div
                        className="d-inline-flex flex-wrap align-items-center justify-content-center gap-1"
                        style={{
                            fontSize: "0.85rem",
                            lineHeight: 1.5,
                            color: "#64748b",
                        }}
                    >
                        <i className="fab fa-whatsapp" style={{ color: "#25D366" }} aria-hidden="true" />
                        <span style={{ marginInlineEnd: "0.35rem" }}>
                            {__("tenant.payment_result_whatsapp_hint")}
                        </span>
                        <a
                            href={waHref}
                            target="_blank"
                            rel="noopener noreferrer"
                            style={{
                                color: "#128C7E",
                                fontWeight: 600,
                                textDecoration: "none",
                                borderBottom: "1px solid rgba(18,140,126,0.35)",
                            }}
                        >
                            {displayPhone}
                        </a>
                    </div>
                ) : null}
            </footer>
        </div>
    );
}
