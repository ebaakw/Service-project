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
import {useEffect, useMemo} from 'react';
import {__} from "@/Utility/Hooks/useTranslate.jsx";
import ScrollUpButton from "@/Layouts/Tenant/components/scrollUp/ScrollUpButton.jsx";

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

    const {flash, locale} = usePage().props;
    const isRtl = locale === 'ar';

    const position = isRtl ? 'top-left' : 'top-right'

    useEffect(() => {
        if (!flash?.type) return;
        const msg = flash.message === 'saved' ? __('tenant.saved') : flash.message;
        if (typeof toast[flash.type] === 'function') {
            toast[flash.type](msg);
        }
    }, [flash])

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

                <Sidebar menuList={resolvedMenuList} shellVariant={shellVariant}/>

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
