import handleMouseEnter from "@/Layouts/Tenant/components/sidebar/helper.js";
import {Link, usePage} from "@inertiajs/react";
import {__} from "@/Utility/Hooks/useTranslate.jsx";
import classNames from "classnames";
import {hasAnyPermissions} from "@/Utility/Hooks/usePermission.jsx";
import {routeUrl} from "@/Utility/routeUrl.js";
import {usePlanFeatures} from "@/Utility/Hooks/usePlanFeatures.jsx";
import { prefetchInertiaPageForRoute } from "@/Utility/inertiaPrefetch.js";

const GroupMenu = ({menu, index}) => {

    const {locale} = usePage().props
    const {hasFeature} = usePlanFeatures();

    if (menu?.requiredFeature && !hasFeature(menu.requiredFeature)) {
        return null;
    }

    const currentRouteName = typeof route === 'function' ? route().current() : null;

    const checkRoute = (children) => {
        let check = false;
        if (typeof route !== 'function') return false;
        // عند فتح كشف حساب العميل نعتبر المستخدم داخل قسم العملاء
        if (currentRouteName === 'reports.customer_statement') {
            return children.some((el) => el.route === 'clients.index');
        }
        children.forEach((element) => {
            if (route().current(element.route)) check = true;
        });
        return check;
    };

    const isChildActive = (child) => {
        if (typeof route !== 'function') return false;
        if (route().current(child.route)) return true;
        // عند فتح كشف حساب العميل نظلّل "العملاء"
        if (currentRouteName === 'reports.customer_statement' && child.route === 'clients.index') return true;
        return false;
    };
    return (
        <>
            {hasAnyPermissions(menu.permissions) && (
                <li
                    key={index}
                    className={checkRoute(menu.children) ? 'menu-open nav-item' : 'nav-item'}
                >
                    <a
                        href="#"
                        id={menu.name}
                        className={checkRoute(menu.children) ? 'ok nav-link active' : 'ok nav-link'}
                        onClick={handleMouseEnter}
                    >
                        <i className={'ok nav-icon fas ' + menu.icon + ' mr-2'}></i>
                        <p className="ok mb-0">
                            {__(menu.name)}
                            <i
                                className={classNames('right fas', {
                                    'fa-angle-right': locale === 'en',
                                    'fa-angle-left': locale !== 'en'
                                })}
                            ></i>
                        </p>
                    </a>

                    <ul className="nav nav-treeview">
                        {menu.children.map((child, index) =>
                            child?.requiredFeature && !hasFeature(child.requiredFeature)
                                ? null
                                : hasAnyPermissions(child.permissions) && (
                                      <li key={index + 'children'} className="nav-item">
                                          <Link
                                              href={routeUrl(child.route)}
                                              onMouseEnter={() => prefetchInertiaPageForRoute(child.route)}
                                              className={isChildActive(child) ? 'active nav-link' : 'nav-link'}
                                          >
                                              <i className="far fa-circle nav-icon mr-2"></i>
                                              <p className="mb-0">{__(child.name)}</p>
                                          </Link>
                                      </li>
                                  )
                        )}
                    </ul>
                </li>
            )}
        </>
    )
}

export default GroupMenu

