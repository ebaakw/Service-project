// import Loader from '@/Pages/Includes/Dashboard/Loader.jsx'
import Navbar from "@/Layouts/Tenant/components/navbar/Navbar.jsx";
import Sidebar from "@/Layouts/Tenant/components/sidebar/Sidebar.jsx";
import Breadcrumb from "@/Layouts/Tenant/components/breadcrumb/Breadcrumb.jsx";
import Footer from "@/Layouts/Tenant/components/footer/Footer.jsx";
import config from "@/Config/config.js";
import classNames from "classnames";
import { Head, usePage, router } from '@inertiajs/react'
import {toast, ToastContainer} from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import './css/table-middle-cell.css'
import './css/progress.css'
import {useEffect, useMemo, useState} from 'react';
import {__} from "@/Utility/Hooks/useTranslate.jsx";
import ScrollUpButton from "@/Layouts/Tenant/components/scrollUp/ScrollUpButton.jsx";
import SubscriptionBanner from "@/Components/Subscription/SubscriptionBanner.jsx";
import axios from "axios";

const MainLayout = ({children, title = 'page', name}) => {

    const {flash, locale, tenant_latest_unread_notification} = usePage().props;
    const isRtl = locale === 'ar';

    const position = isRtl ? 'top-left' : 'top-right'

    // flash.type && toast[flash.type](flash.message)
    const [showAnnouncementModal, setShowAnnouncementModal] = useState(false);
    const [markingAsRead, setMarkingAsRead] = useState(false);

    const announcement = useMemo(() => {
        if (!tenant_latest_unread_notification || !tenant_latest_unread_notification.id) return null;
        return tenant_latest_unread_notification;
    }, [tenant_latest_unread_notification]);

    useEffect(() => {
        if (!flash?.type) return;
        const msg = flash.message === 'saved' ? __('tenant.saved') : flash.message;
        toast[flash.type](msg);
    }, [flash])

    useEffect(() => {
        if (!announcement?.id) {
            return;
        }

        // ✅ فقط نفتح المودال، بدون request
        setShowAnnouncementModal(true);
    }, [announcement?.id])

    // ✅ نعمل request فقط بعد فتح المودال
    useEffect(() => {
        if (!showAnnouncementModal || !announcement?.id) return;

        axios.post(route('tenant.notifications.mark-popup-shown'), {id: announcement.id}).catch(() => {});
    }, [showAnnouncementModal, announcement?.id]);

    useEffect(() => {
        if (!showAnnouncementModal) {
            return;
        }

        const onKeyDown = (e) => {
            if (e.key === 'Escape') {
                setShowAnnouncementModal(false);
            }
        };

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        window.addEventListener('keydown', onKeyDown);

        return () => {
            window.removeEventListener('keydown', onKeyDown);
            document.body.style.overflow = previousOverflow;
        };
    }, [showAnnouncementModal]);

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

                <Navbar/>

                <Sidebar/>

                <Head title={title === 'page' || title === '' ? __(name) : __(title) }/>

                <ToastContainer position={position} rtl={locale === 'ar'}/>

                {showAnnouncementModal && announcement && (
                    <div
                        className="position-fixed w-100 h-100"
                        style={{
                            top: 0,
                            left: 0,
                            zIndex: 2000,
                            background: 'rgba(0,0,0,.55)',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            padding: '16px',
                        }}
                        role="dialog"
                        aria-modal="true"
                        onClick={() => setShowAnnouncementModal(false)}
                    >
                        <div
                            className="card shadow-lg"
                            style={{
                                width: '100%',
                                maxWidth: '540px',
                                borderRadius: '14px',
                                overflow: 'hidden',
                            }}
                            onClick={(e) => e.stopPropagation()}
                        >
                            <div className="card-header position-relative">
                                <strong className="mb-0 d-block w-100 text-center">
                                    {announcement.subject || __('tenant.notifications')}
                                </strong>
                                <button
                                    type="button"
                                    className="btn btn-sm btn-light"
                                    onClick={() => setShowAnnouncementModal(false)}
                                    aria-label="Close"
                                    style={{
                                        position: 'absolute',
                                        top: '50%',
                                        transform: 'translateY(-50%)',
                                        ...(isRtl ? {left: '12px'} : {right: '12px'}),
                                    }}
                                >
                                    ×
                                </button>
                            </div>
                            <div className="card-body text-center">
                                <div style={{whiteSpace: 'pre-wrap', textAlign: 'center'}}>
                                    {announcement.body || ''}
                                </div>
                            </div>
                            <div
                                className="card-footer"
                                style={{
                                    position: 'relative',
                                    minHeight: '56px',
                                }}
                            >
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary"
                                    onClick={() => setShowAnnouncementModal(false)}
                                    style={{
                                        position: 'absolute',
                                        ...(isRtl ? {right: '12px'} : {left: '12px'}),
                                        top: '50%',
                                        transform: 'translateY(-50%)',
                                    }}
                                >
                                    {isRtl ? 'إلغاء' : 'Cancel'}
                                </button>
                                <button
                                    type="button"
                                    className="btn btn-primary"
                                    disabled={markingAsRead}
                                    onClick={async () => {
                                        setMarkingAsRead(true);
                                        try {
                                            const res = await axios.post(route('tenant.notifications.mark-read'), {id: announcement.id});
                                            if (res?.data?.ok) {
                                                setShowAnnouncementModal(false);
                                                router.reload();
                                            }
                                        } finally {
                                            setMarkingAsRead(false);
                                        }
                                    }}
                                    style={{
                                        position: 'absolute',
                                        ...(isRtl ? {left: '12px'} : {right: '12px'}),
                                        top: '50%',
                                        transform: 'translateY(-50%)',
                                    }}
                                >
                                    {markingAsRead ? '...' : __('tenant.mark_notification_as_read')}
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                <div className="content-wrapper text-sm">
                    <Breadcrumb name={name}/>
                    <section className="content">
                        <div className="container-fluid">
                            <SubscriptionBanner />
                            {children}
                        </div>
                    </section>
                </div>

                <Footer/>
        </div>
    )
}

export default MainLayout;
