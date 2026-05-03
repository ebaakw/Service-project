import {asset} from "@/Utility/Hooks/useAsset.jsx";
import {MenuList} from "@/Navigation/Tenant/index.jsx";
import classNames from "classnames";
import config from "@/Config/config.js";
import GroupMenu from "@/Layouts/Tenant/components/sidebar/menu/GroupMenu.jsx";
import SingleMenu from "@/Layouts/Tenant/components/sidebar/menu/SingleMenu.jsx";
import {usePage} from "@inertiajs/react";
import {useState} from "react";

const Sidebar = () => {
    const {auth, settings, tenant_support_unread_count} = usePage().props;

    const [list, setList] = useState(MenuList);

    const filter = (event) => {
        if (!event.target.value) {
            return setList(MenuList);
        }
        const array = MenuList.filter((item => item.name.toLowerCase().includes(event.target.value)))
        setList(array);
    }

    const sidebarBgClass = settings?.sidebar_bg || "sidebar-dark-primary";

    /**
     *    'sidebar-dark-primary',
     *     'sidebar-dark-warning',
     *     'sidebar-dark-info',
     *     'sidebar-dark-danger',
     *     'sidebar-dark-success',
     *     'sidebar-dark-indigo',
     *     'sidebar-dark-lightblue',
     *     'sidebar-dark-navy',
     *     'sidebar-dark-purple',
     *     'sidebar-dark-fuchsia',
     *     'sidebar-dark-pink',
     *     'sidebar-dark-maroon',
     *     'sidebar-dark-orange',
     *     'sidebar-dark-lime',
     *     'sidebar-dark-teal',
     *     'sidebar-dark-olive',
     *     'sidebar-light-primary',
     *     'sidebar-light-warning',
     *     'sidebar-light-info',
     *     'sidebar-light-danger',
     *     'sidebar-light-success',
     *     'sidebar-light-indigo',
     *     'sidebar-light-lightblue',
     *     'sidebar-light-navy',
     *     'sidebar-light-purple',
     *     'sidebar-light-fuchsia',
     *     'sidebar-light-pink',
     *     'sidebar-light-maroon',
     *     'sidebar-light-orange',
     *     'sidebar-light-lime',
     *     'sidebar-light-teal',
     *     'sidebar-light-olive'
     */
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
                <i className="fas fa-tshirt mx-3 mt-1 text-green"></i>

                <span
                    className="brand-text font-weight-light font-weight-bold text-uppercase">{auth.user?.tenant}</span>
            </a>
            <div className="sidebar">
                {/*Sidebar user panel (optional)*/}
                {/*<div className="user-panel mt-2 pb-3 d-flex">*/}
                {/*    <div className="image">*/}
                {/*        <img src={asset('dashboard/dist/images/role.png')}*/}
                {/*             className="img-circle elevation-2"*/}
                {/*             alt="User Image"*/}
                {/*        />*/}
                {/*    </div>*/}
                {/*    <div className="info">*/}
                {/*        <a href="#" className="d-block text-uppercase">*/}
                {/*            <span>{auth.user?.name}</span>*/}
                {/*            <span> - </span>*/}
                {/*            <span>{auth.user?.roles}</span>*/}
                {/*        </a>*/}
                {/*    </div>*/}
                {/*</div>*/}

                <div className="form-inline">
                    <div className="input-group" data-widget="sidebar-search">
                        <input className="form-control form-control-sidebar"
                               type="search"
                               placeholder="Search"
                               aria-label="Search"
                               onChange={filter}
                        />
                        <div className="input-group-append">
                            <button className="btn btn-sidebar">
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
                                        menu={{
                                            ...menu,
                                            badgeCount: menu.route === 'contactus.create'
                                                ? tenant_support_unread_count
                                                : 0,
                                        }}
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

