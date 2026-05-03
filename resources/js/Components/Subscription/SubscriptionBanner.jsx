import React, { useState } from "react";
import { usePage } from "@inertiajs/react";
import { __ } from "@/Utility/Hooks/useTranslate.jsx";

const SubscriptionBanner = () => {
    const { subscription } = usePage().props || {};

    const [dismissed, setDismissed] = useState(false);

    if (dismissed) {
        return null;
    }

    if (!subscription) {
        return null;
    }

    const { days_remaining, is_expiring_soon } = subscription;

    // نعرض الشريط في الحالات التالية:
    // - إذا كان هناك عدد أيام رقمي داخل نافذة التحذير
    // - أو إذا كان backend يعلّمه كـ expiring_soon بدون رقم (fallback)
    const numericDays = typeof days_remaining === "number" ? days_remaining : null;

    // Professional staged reminders (common SaaS pattern):
    // - <= 30 days: show once (early)
    // - <= 14 days: show once (medium)
    // - <= 7 days: show once (strong)
    // - <= 3 days: always show on all pages (urgent)
    // - < 24 hours: always show + time granularity (critical)
    const stage = (() => {
        if (numericDays === null) return null;
        if (numericDays < 0) return "expired";
        if (numericDays < 1) return "critical_24h";
        if (numericDays <= 3) return "urgent_3d";
        if (numericDays <= 7) return "strong_7d";
        if (numericDays <= 14) return "medium_14d";
        if (numericDays <= 30) return "early_30d";
        return null;
    })();

    // <= 3 أيام: يظهر في كل الصفحات (مهم جدًا)
    // <= 30 يوم: يظهر مرة واحدة فقط (غير مزعج)
    const seenKey = stage ? `els_subscription_banner_seen_stage_${stage}` : null;
    const stageSeen = (() => {
        if (!seenKey) return false;
        // Stages that should ALWAYS show are never "seen"
        if (stage === "urgent_3d" || stage === "critical_24h") return false;
        try {
            return sessionStorage.getItem(seenKey) === "1";
        } catch {
            return false;
        }
    })();

    const shouldShow =
        // Use staged logic when days_remaining is present
        (stage !== null && (stage === "urgent_3d" || stage === "critical_24h" || !stageSeen)) ||
        // Fallback: if backend marks it expiring soon but numericDays is missing, show it (once per page)
        (is_expiring_soon === true && numericDays === null);

    if (!shouldShow) {
        return null;
    }

    let className = "alert ";
    // Color semantics:
    // - critical_24h, urgent_3d, expired => red
    // - strong_7d => warning (amber)
    // - medium_14d, early_30d => info (calm)
    if (stage === "critical_24h" || stage === "urgent_3d" || stage === "expired") {
        className += "alert-danger";
    } else if (stage === "strong_7d") {
        className += "alert-warning";
    } else {
        className += "alert-info";
    }

    let message;
    if (typeof numericDays === "number") {
        if (numericDays > 1) {
            // أكثر من يوم: نعرض أيام (تقريب لأعلى)
            const roundedDays = Math.ceil(numericDays);
            message = __("tenant.subscription_expires_in_days", { days: roundedDays });
        } else if (numericDays > 0) {
            // أقل من يوم: نعرض وقت أدق (ساعات/دقائق)
            const totalMinutes = Math.max(1, Math.round(numericDays * 24 * 60));
            const hours = Math.floor(totalMinutes / 60);
            const minutes = totalMinutes % 60;
            if (hours >= 1 && minutes >= 1) {
                message = __("tenant.subscription_expires_in_hours_minutes", { hours, minutes });
            } else if (hours >= 1) {
                message = __("tenant.subscription_expires_in_hours", { hours });
            } else {
                message = __("tenant.subscription_expires_in_minutes", { minutes });
            }
        } else if (numericDays === 0) {
            message = __("tenant.subscription_expires_today");
        } else {
            // أقل من 0: انتهى بالفعل
            message = __("tenant.subscription_expired_recently");
        }
    } else {
        message = __("tenant.subscription_expires_soon");
    }

    return (
        <div className={className + " d-flex justify-content-between align-items-center"} role="alert">
            <div>
                <strong>{__("tenant.subscription_status")}:</strong> {message}
            </div>
            <button
                type="button"
                className="btn btn-sm btn-outline-light"
                onClick={() => {
                    // Mark as seen once per session for non-urgent stages.
                    // Urgent stages (<= 3 days, < 24h) should still re-appear on navigation.
                    if (seenKey && stage !== "urgent_3d" && stage !== "critical_24h") {
                        try {
                            sessionStorage.setItem(seenKey, "1");
                        } catch {
                            // ignore storage errors
                        }
                    }
                    setDismissed(true);
                }}
            >
                ×
            </button>
        </div>
    );
};

export default SubscriptionBanner;

