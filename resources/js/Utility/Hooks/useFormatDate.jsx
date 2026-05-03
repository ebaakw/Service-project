import { useCallback } from "react";
import { usePage } from "@inertiajs/react";
import {
    formatInTimezone,
    formatDateTime as formatDateTimeUtil,
    formatDate as formatDateUtil,
    formatTime as formatTimeUtil,
} from "@/Utility/formatDateTime.js";

/**
 * توقيت النظام من إعدادات المستأجر (يُشاركه HandleInertiaRequests).
 * يُستخدم لعرض كل التواريخ في الواجهة وفق نفس التوقيت.
 */
const DEFAULT_TIMEZONE = "UTC";

/**
 * Hook يُرجع دوال تنسيق التاريخ والوقت وفق توقيت النظام.
 * استخدمه في أي صفحة لعرض تواريخ من الـ API أو قاعدة البيانات بشكل موحّد.
 *
 * @example
 * const { formatDateTime, formatDate, timezone } = useFormatDate();
 * formatDateTime(row.created_at);  // بتوقيت النظام
 * formatDate(row.from);            // تاريخ فقط
 */
export function useFormatDate() {
    const timezone =
        (typeof usePage === "function" && usePage()?.props?.timezone) || DEFAULT_TIMEZONE;

    const formatDateTime = useCallback(
        (iso, options) => formatInTimezone(iso, timezone, options ?? {}),
        [timezone]
    );
    const formatDate = useCallback(
        (iso) => formatDateUtil(iso, timezone),
        [timezone]
    );
    const formatTime = useCallback(
        (iso) => formatTimeUtil(iso, timezone),
        [timezone]
    );

    return {
        formatDateTime,
        formatDate,
        formatTime,
        timezone,
    };
}

export default useFormatDate;
