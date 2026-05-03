import handleMouseEnter from "@/Layouts/Tenant/components/sidebar/helper.js";
import {Link} from "@inertiajs/react";
import {__} from "@/Utility/Hooks/useTranslate.jsx";
import {hasAnyPermissions} from "@/Utility/Hooks/usePermission.jsx";
import {routeUrl} from "@/Utility/routeUrl.js";
import {usePlanFeatures} from "@/Utility/Hooks/usePlanFeatures.jsx";
import { prefetchInertiaPageForRoute } from "@/Utility/inertiaPrefetch.js";

const SingleMenu = ({menu, index}) => {
    const {hasFeature} = usePlanFeatures();

    if (menu?.requiredFeature && !hasFeature(menu.requiredFeature)) {
        return null;
    }

    const badgeCount = menu.badgeCount || 0;

    return (
        <>
            {hasAnyPermissions(menu.permissions) && (
                <li
                    key={index}
                    className={
                        typeof route === 'function' && route().current(menu.route)
                            ? 'menu-open nav-item'
                            : 'nav-item'
                    }
                    onClick={handleMouseEnter}
                >
                    <Link
                        href={routeUrl(menu.route)}
                        id={menu.name}
                        onMouseEnter={() => prefetchInertiaPageForRoute(menu.route)}
                        className={
                            typeof route === 'function' && route().current(menu.route)
                                ? 'ok nav-link active'
                                : 'ok nav-link'
                        }
                    >
                        <i className={'ok nav-icon fas ' + menu.icon + ' mr-2'}></i>
                        <p className="ok mb-0">
                            {__(menu.name)}
                            {badgeCount > 0 && (
                                <span className="right badge badge-warning ml-2">
                                    {badgeCount}
                                </span>
                            )}
                        </p>
                    </Link>
                </li>
            )}
        </>
    )
}

export default SingleMenu

