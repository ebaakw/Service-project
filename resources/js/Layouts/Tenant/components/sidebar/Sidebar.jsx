import classNames from "classnames";
import config from "@/Config/config.js";
import GroupMenu from "@/Layouts/Tenant/components/sidebar/menu/GroupMenu.jsx";
import SingleMenu from "@/Layouts/Tenant/components/sidebar/menu/SingleMenu.jsx";
import {usePage} from "@inertiajs/react";
import {useEffect, useMemo, useState} from "react";
import {__} from "@/Utility/Hooks/useTranslate.jsx";

const brandIconClass = (shellVariant) => {
    if (shellVariant === 'platform') {
        return 'fas fa-user-shield mx-3 mt-1 text-info';
    }
    if (shellVariant === 'company') {
        return 'fas fa-building mx-3 mt-1 text-success';
    }
    return 'fas fa-layer-group mx-3 mt-1 text-primary';
};

const Sidebar = ({menuList: menuListProp, shellVariant}) => {
    const {auth, settings, currentCompany} = usePage().props;

    const baseMenu = menuListProp ?? [];

    const [list, setList] = useState(baseMenu);

    useEffect(() => {
        setList(baseMenu);
    }, [baseMenu]);

    const filter = (event) => {
        if (!event.target.value) {
            return setList(baseMenu);
        }
        const q = event.target.value.toLowerCase();
        const array = baseMenu.filter((item) => item.name.toLowerCase().includes(q));
        setList(array);
    };

    const sidebarBgClass = settings?.sidebar_bg || "sidebar-dark-primary";

    const brandIcon = useMemo(() => brandIconClass(shellVariant), [shellVariant]);

    const brandText = useMemo(() => {
        if (shellVariant === 'platform') {
            return __('dashboard.nav.platform_admin');
        }
        if (shellVariant === 'company') {
            return currentCompany?.display_name || auth?.user?.tenant || '';
        }
        return auth?.user?.tenant || '';
    }, [shellVariant, currentCompany?.display_name, auth?.user?.tenant]);

    return (
        <aside
            className={classNames(`main-sidebar elevation-4 ${sidebarBgClass}`, {
                'sidebar-no-expand': config.sideBar.autoExpand
            })}
        >
            <a href="#"
               className={classNames('brand-link', {
                   'text-sm': config.smallText.brand
               })}
            >
                <i className={brandIcon}></i>

                <span
                    className="brand-text font-weight-light font-weight-bold text-uppercase">{brandText}</span>
            </a>
            <div className="sidebar">
                <div className="form-inline">
                    <div className="input-group" data-widget="sidebar-search">
                        <input className="form-control form-control-sidebar"
                               type="search"
                               placeholder="Search"
                               aria-label="Search"
                               onChange={filter}
                        />
                        <div className="input-group-append">
                            <button type="button" className="btn btn-sidebar">
                                <i className="fas fa-search fa-fw"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <nav className="mt-2">
                    <ul className={
                        classNames('nav nav-pills nav-sidebar flex-column nav-child-indent', {
                            'text-sm': config.smallText.sideBarNav
                        })}
                        role="menu"
                        style={{transitionProperty: 'all', transitionDuration: '2s'}}
                    >
                        {
                            list.map((menu, index) => (
                                menu.hasOwnProperty('children')
                                    ? <GroupMenu key={index + 'menu'} menu={menu} index={index}/>
                                    : <SingleMenu
                                        key={index + 'menu'}
                                        menu={menu}
                                        index={index}
                                    />
                            ))
                        }
                    </ul>
                </nav>
            </div>
        </aside>
    )
}

export default Sidebar
