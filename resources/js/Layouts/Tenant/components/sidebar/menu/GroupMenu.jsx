import handleMouseEnter from "@/Layouts/Tenant/components/sidebar/helper.js";
import {Link, usePage} from "@inertiajs/react";
import {__} from "@/Utility/Hooks/useTranslate.jsx";
import classNames from "classnames";
import { hasAnyPermissions, hasPermission } from "@/Utility/Hooks/usePermission.jsx";
import {routeUrl} from "@/Utility/routeUrl.js";
import {usePlanFeatures} from "@/Utility/Hooks/usePlanFeatures.jsx";
import { prefetchInertiaPageForRoute } from "@/Utility/inertiaPrefetch.js";

const GroupMenu = ({menu, index}) => {

    const {locale} = usePage().props
    const {hasFeature} = usePlanFeatures();
    const badgeCount = menu.badgeCount || 0;
    const badgeSpacingClass = locale === 'ar' ? 'mr-2' : 'ml-2';

    if (menu?.requiredFeature && !hasFeature(menu.requiredFeature)) {
        return null;
    }

    const childRouteMatches = (element) => {
        if (typeof route !== 'function') return false;
        if (route().current(element.route)) return true;
        const patterns = element.activePatterns;
        if (!Array.isArray(patterns)) return false;
        return patterns.some((p) => {
            try {
                return route().current(p);
            } catch {
                return false;
            }
        });
    };

    const checkRoute = (children) => {
        if (typeof route !== 'function') return false;
        return children.some((el) => childRouteMatches(el));
    };

    const isChildActive = (child) => childRouteMatches(child);
    const canSee =
        menu.bypassPermissionCheck === true
        || (menu.permission ? hasPermission(menu.permission) : true)
        || hasAnyPermissions(menu.permissions || []);

    return (
        <>
            {canSee && (
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
                            {badgeCount > 0 && (
                                <span className={classNames('right badge badge-warning', badgeSpacingClass)}>
                                    {badgeCount}
                                </span>
                            )}
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
                                : (child.bypassPermissionCheck === true
                                      || (child.permission ? hasPermission(child.permission) : true)
                                      || hasAnyPermissions(child.permissions || [])) && (
                                      <li key={index + 'children'} className="nav-item">
                                          <Link
                                              href={routeUrl(child.route, child.params || {})}
                                              onMouseEnter={() => prefetchInertiaPageForRoute(child.route)}
                                              className={isChildActive(child) ? 'active nav-link' : 'nav-link'}
                                          >
                                              <i className="far fa-circle nav-icon mr-2"></i>
                                              <p className="mb-0">
                                                  {__(child.name)}
                                                  {(child.badgeCount || 0) > 0 && (
                                                      <span className={classNames('right badge badge-warning', badgeSpacingClass)}>
                                                          {child.badgeCount}
                                                      </span>
                                                  )}
                                              </p>
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

