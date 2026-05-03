import React from "react";
import Swal from "sweetalert2";
import {__} from "@/Utility/Hooks/useTranslate.jsx";
import {usePage} from "@inertiajs/react";

export default function UpgradeFeatureNotice({
    featureTitle,
    featureKey,
    className = "",
}) {
    const {support_whatsapp_number: supportWhatsappNumberRaw} = usePage().props || {};

    const normalizeWhatsapp = (raw) => {
        if (!raw) return "";
        const s = String(raw).trim();
        // keep digits only, allow leading +
        const cleaned = s.replace(/[^\d+]/g, "");
        return cleaned.startsWith("+") ? cleaned.slice(1).replace(/[^\d]/g, "") : cleaned.replace(/[^\d]/g, "");
    };

    const supportWhatsappNumber = normalizeWhatsapp(supportWhatsappNumberRaw);

    const onLearnMore = () => {
        if (supportWhatsappNumber) {
            const text = encodeURIComponent(__("tenant.feature_upgrade_message", {feature: featureTitle}));
            const url = `https://wa.me/${supportWhatsappNumber}?text=${text}`;
            window.open(url, "_blank", "noopener,noreferrer");
            return;
        }

        Swal.fire({
            icon: "info",
            title: __("tenant.feature_upgrade_required"),
            text: __("tenant.feature_upgrade_message", {feature: featureTitle}),
            confirmButtonText: __("tenant.ok"),
        });
    };

    return (
        <div className={`card card-outline card-warning ${className}`}>
            <div className="card-body">
                <div className="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                    <div>
                        <div className="fw-bold">{featureTitle}</div>
                        <div className="text-muted small">
                            {__("tenant.feature_upgrade_message", {feature: featureTitle})}
                        </div>
                        {featureKey ? (
                            <div className="text-muted small" style={{direction: "ltr"}}>
                                <span className="opacity-75">({featureKey})</span>
                            </div>
                        ) : null}
                    </div>

                    <button type="button" className="btn btn-outline-warning btn-sm" onClick={onLearnMore}>
                        {supportWhatsappNumber ? __("tenant.whatsapp") : __("tenant.upgrade")}
                    </button>
                </div>
            </div>
        </div>
    );
}

