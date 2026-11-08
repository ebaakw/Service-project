import Navbar from "@/Layouts/Tenant/components/navbar/Navbar.jsx";
import Sidebar from "@/Layouts/Tenant/components/sidebar/Sidebar.jsx";
import Footer from "@/Layouts/Tenant/components/footer/Footer.jsx";
import {platformMenu} from "@/Navigation/platformMenu.js";
import {companyMenu} from "@/Navigation/companyMenu.js";
import config from "@/Config/config.js";
import classNames from "classnames";
import { Head, usePage } from '@inertiajs/react'
import {toast, ToastContainer} from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import './css/table-middle-cell.css'
import './css/progress.css'
import {useEffect, useMemo, useRef} from 'react';
import {__} from "@/Utility/Hooks/useTranslate.jsx";
import ScrollUpButton from "@/Layouts/Tenant/components/scrollUp/ScrollUpButton.jsx";
import { filterMenuByPermissions } from "@/Support/permissions.js";
import { playDelayAlertSound } from "@/Utility/technicianDelayAlerts.js";

const applyBadges = (items, badges) => {
    if (!Array.isArray(items)) return [];
    return items.map((item) => {
        const next = { ...item };
        if (next.badge && badges && typeof badges[next.badge] === 'number') {
            next.badgeCount = badges[next.badge];
        }
        if (Array.isArray(next.children)) {
            next.children = applyBadges(next.children, badges);
        }
        return next;
    });
};

const MainLayout = ({
    children,
    title = 'page',
    menuList: menuListProp,
    shellVariant,
}) => {
    if (shellVariant !== 'platform' && shellVariant !== 'company') {
        if (typeof console !== 'undefined' && console.warn) {
            console.warn(
                `MainLayout: shellVariant must be 'platform' or 'company' (got ${shellVariant}).`
            );
        }
    }

    const resolvedMenuList = useMemo(() => {
        if (menuListProp) return menuListProp;
        if (shellVariant === 'platform') return platformMenu;
        if (shellVariant === 'company') return companyMenu;
        return [];
    }, [menuListProp, shellVariant]);

    const { flash, locale, auth, sidebarBadges } = usePage().props;
    const isRtl = locale === 'ar';

    const position = isRtl ? 'top-left' : 'top-right'

    const userPermissions = auth?.user?.permissions ?? [];

    const visibleMenuList = useMemo(() => {
        const filtered = filterMenuByPermissions(resolvedMenuList, userPermissions);
        return applyBadges(filtered, sidebarBadges || {});
    }, [resolvedMenuList, userPermissions, sidebarBadges]);

    useEffect(() => {
        if (!flash?.type) return;
        const msg =
            flash.message === 'saved' || flash.message === 'Saved.'
                ? __('tenant.saved')
                : flash.message;
        if (typeof toast[flash.type] === 'function') {
            toast[flash.type](msg);
        }
    }, [flash])

    const knownDelayIdsRef = useRef(new Set());
    const delayPollReadyRef = useRef(false);

    useEffect(() => {
        if (shellVariant !== 'platform' && shellVariant !== 'company') {
            return undefined;
        }

        const endpoint =
            shellVariant === 'platform'
                ? route('platform.alerts.technician-delays')
                : route('company.alerts.technician-delays');

        let cancelled = false;

        const poll = async () => {
            try {
                const response = await fetch(endpoint, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok || cancelled) {
                    return;
                }

                const payload = await response.json();
                const delays = Array.isArray(payload?.delays) ? payload.delays : [];
                const nextIds = new Set(delays.map((item) => String(item.id)));

                if (!delayPollReadyRef.current) {
                    knownDelayIdsRef.current = nextIds;
                    delayPollReadyRef.current = true;
                    return;
                }

                const fresh = delays.filter((item) => !knownDelayIdsRef.current.has(String(item.id)));
                knownDelayIdsRef.current = nextIds;

                if (fresh.length === 0) {
                    return;
                }

                const first = fresh[0];
                const typeLabel =
                    first.delay_type === 'arrival'
                        ? __('dashboard.delay_alerts.arrival_type')
                        : __('dashboard.delay_alerts.accept_type');

                toast.warn(
                    `${__('dashboard.delay_alerts.toast_title')}: ${typeLabel} — ${first.order_number ?? ''}`.trim(),
                );
                playDelayAlertSound();
            } catch {
                // Ignore transient network/auth errors during polling.
            }
        };

        void poll();
        const timer = window.setInterval(() => {
            void poll();
        }, 30000);

        return () => {
            cancelled = true;
            window.clearInterval(timer);
        };
    }, [shellVariant]);

    return (
        <div
            className={classNames('wrapper', {
                'dark-mode': config.isDark,
                'sidebar-collapse': config.sideBar.collapse,
                'layout-fixed': config.sideBar.fixed,
                'sidebar-mini': config.sideBar.mini,
                'sidebar-mini-md': config.sideBar.miniMd,
                'sidebar-mini-xs': config.sideBar.miniXs,
                'layout-footer-fixed': config.footer.fixed,
                'text-sm': config.smallText.body
            })}
        >
                <ScrollUpButton />

                <Navbar shellVariant={shellVariant}/>

                <Sidebar menuList={visibleMenuList} shellVariant={shellVariant}/>

                <ToastContainer position={position} rtl={locale === 'ar'}/>

                <div className="content-wrapper text-sm">
                    <section className="content">
                        <div className="container-fluid">
                            {children}
                        </div>
                    </section>
                </div>

                <Footer/>
        </div>
    )
}

export default MainLayout;
