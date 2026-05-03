import classNames from "classnames";
import config from "@/Config/config.js";
import {__} from "@/Utility/Hooks/useTranslate.jsx";
import {Link, usePage, router} from "@inertiajs/react";
import {linkRoute} from "@/Utility/routeUrl.js";
import {asset} from "@/Utility/Hooks/useAsset.jsx";
import {useState, useEffect} from "react";
import {hasAnyPermissions} from "@/Utility/Hooks/usePermission.jsx";

const Navbar = () => {
    const {locale, today, auth, timezone, tenant_notifications_unread_count} = usePage().props;
    const tz = timezone || 'UTC';

    // تنسيق الساعة بأرقام إنجليزية مع AM/PM
    const formatTime = () =>
        new Date().toLocaleTimeString('en-US', {
            timeZone: tz,
            hour: '2-digit',
            minute: '2-digit',
            hour12: true,
        });

    const [currentTime, setCurrentTime] = useState(formatTime);

    useEffect(() => {
        const timer = setInterval(() => setCurrentTime(formatTime()), 1000);
        return () => clearInterval(timer);
    }, [tz]);

    const [currentBranch, setCurrentBranch] = useState(auth?.user?.currentBranch || null);
    const [unreadCount, setUnreadCount] = useState(tenant_notifications_unread_count || 0);
    const [unreadRows, setUnreadRows] = useState([]);
    const [loadingUnread, setLoadingUnread] = useState(false);

    useEffect(() => {
        setUnreadCount(tenant_notifications_unread_count || 0);
    }, [tenant_notifications_unread_count]);

    useEffect(() => {
        setCurrentBranch(auth?.user?.currentBranch || null);
    }, [auth?.user?.currentBranch]);

    const loadUnreadNotifications = async () => {
        try {
            setLoadingUnread(true);
            const res = await fetch(route('tenant.notifications.unread'), {
                headers: {
                    'Accept': 'application/json',
                }
            });
            const json = await res.json();
            setUnreadRows(json?.data || []);
        } catch (e) {
            setUnreadRows([]);
        } finally {
            setLoadingUnread(false);
        }
    };

    const handleSelect = (branch) => {
        if (!auth?.user?.branches?.length) {
            return;
        }
        if (branch.id !== auth.user.currentBranch?.id) {
            router.post(
                '/branches/change',
                { branch_id: branch.id },
                {
                    onSuccess: () => {
                        location.reload();
                        setCurrentBranch(branch);
                    },
                    onError: () => {
                        console.error('Failed to change branch.');
                    },
                }
            );
        }
    };

    const items = [
        {text: 'Branch 1'},
        {text: 'Branch 2'},
        {text: 'Branch 3'},
    ];

    const changeLang = (event) => {
        event.preventDefault();
        const lang = locale === 'en' ? 'ar' : 'en';

        // Use a normal full navigation so Blade can regenerate dir + window._translations
        window.location.href = route('locale.switch', { locale: lang });
    }

    const capitalizeFirstLetter = (val) => {
        return String(val).charAt(0).toUpperCase() + String(val).slice(1);
    }

    return (
        <nav
            className={classNames(
                'main-header navbar navbar-expand', {
                    'dropdown-legacy': config.dropdownLegacy,
                    'border-bottom-0': config.noBorder,
                    'text-sm': config.smallText.navbar
                }
            )}
        >
            <ul className="navbar-nav">
                <li className="nav-item">
                    <a
                        className="nav-link"
                        data-widget="pushmenu"
                        href="#"
                        role="button"
                    >
                        <i className="fas fa-bars"></i>
                    </a>
                </li>
                {/*<li className="nav-item d-none d-sm-inline-block">*/}
                {/*    <a href="" className="nav-link">Home</a>*/}
                {/*</li>*/}
                {/*<li className="nav-item d-none d-sm-inline-block">*/}
                {/*    <a href="#" className="nav-link">Contact</a>*/}
                {/*</li>*/}
            </ul>

            {/*Right navbar links */}
            <ul className="navbar-nav ml-auto">
                {
                    !route().current('invoices.create') && (
                        <li className="nav-item d-none d-sm-inline-block">
                            <Link href={linkRoute('invoices.create')} className="btn btn-primary btn-sm mt-2">
                                <i className="fas fa-money-bill-wave mx-1"></i>
                                {__('tenant.selling_point')}
                            </Link>
                        </li>
                    )
                }
                {
                    route().current('invoices.create') && (
                        <>
                            {hasAnyPermissions(['create_driver_orders']) && (
                                <li className="nav-item d-none d-sm-inline-block mr-1">
                                    <button
                                        type="button"
                                        className="btn btn-outline-primary py-2 px-2"
                                        style={{ minWidth: '38px' }}
                                        title={__('tenant.add_driver_order') !== 'tenant.add_driver_order'
                                            ? __('tenant.add_driver_order')
                                            : 'إضافة طلب سائق'}
                                        onClick={() => window.dispatchEvent(new CustomEvent('invoices-create-open-driver-order'))}
                                    >
                                        <i className="fas fa-truck" />
                                        <span className="ml-1">+</span>
                                    </button>
                                </li>
                            )}
                            {hasAnyPermissions(['create_expenses']) && (
                                <li className="nav-item d-none d-sm-inline-block">
                                    <button
                                        type="button"
                                        className="btn btn-success py-2 px-2"
                                        style={{ minWidth: '38px' }}
                                        title={__('tenant.add_expense')}
                                        onClick={() => window.dispatchEvent(new CustomEvent('invoices-create-open-expense'))}
                                    >
                                        <i className="fas fa-receipt"/>
                                    </button>
                                </li>
                            )}
                            <li className="nav-item d-none d-sm-inline-block ml-1">
                                <button
                                    type="button"
                                    className="btn btn-primary py-2 px-2"
                                    style={{ minWidth: '38px' }}
                                    title="آلة حاسبة"
                                    onClick={() =>
                                        window.open('/calculator', 'Calculator', 'width=340,height=560,title=ahmed')
                                    }
                                >
                                    <i className="fas fa-calculator"/>
                                </button>
                            </li>
                        </>
                    )
                }

                <li className="nav-item d-none d-sm-inline-block text-sm mt-1">
                    <a href={'#'} className="nav-link">
                        <span className='mx-2'> {__('tenant.today_date')}: </span>
                        {today}
                        <span className='mx-2'> | </span>
                        <span className='mx-1'>{__('tenant.system_time')}:</span>
                        <span className='mx-1'>{currentTime}</span>
                    </a>
                </li>

                <li className="nav-item d-none d-sm-inline-block text-sm mt-1">
                    <a href={'#'} onClick={changeLang} className="nav-link">
                        <i className="fa fa-globe mx-1"></i>
                        {__('tenant.lang')}
                    </a>
                </li>

                {/* Notifications bell */}
                <li className="nav-item dropdown">
                    <a
                        href="#"
                        className="nav-link"
                        data-toggle="dropdown"
                        aria-expanded="false"
                        onClick={() => loadUnreadNotifications()}
                    >
                        <i className="far fa-bell"></i>
                        {unreadCount > 0 && (
                            <span className="badge badge-warning navbar-badge">
                                {unreadCount}
                            </span>
                        )}
                    </a>
                    <div
                        className={classNames('dropdown-menu dropdown-menu-lg dropdown-menu-right', {
                            'dropdown-menu-right': (locale === 'en'),
                            'dropdown-menu-left': (locale === 'ar'),
                        })}
                    >
                        <span className="dropdown-item dropdown-header">
                            {unreadCount > 0
                                ? __('tenant.unread_notifications_count', {count: unreadCount})
                                : __('tenant.no_notifications')}
                        </span>
                        <div className="dropdown-divider"></div>

                        {loadingUnread ? (
                            <span className="dropdown-item text-muted">{__('tenant.loading') !== 'tenant.loading' ? __('tenant.loading') : '...'}</span>
                        ) : (
                            <>
                                {unreadRows.slice(0, 10).map((n) => (
                                    <Link
                                        key={n.id}
                                        href={linkRoute('tenant.notifications.show', n.id)}
                                        className="dropdown-item"
                                        onClick={() => setUnreadCount((c) => Math.max(0, (c || 0) - 1))}
                                    >
                                        <div className="text-truncate">
                                            <strong>{n.subject || __('tenant.notifications')}</strong>
                                        </div>
                                        {n.body_preview && (
                                            <div className="text-muted text-truncate">
                                                {n.body_preview}
                                            </div>
                                        )}
                                    </Link>
                                ))}
                                {unreadRows.length === 0 && (
                                    <span className="dropdown-item text-muted">{__('tenant.no_notifications')}</span>
                                )}
                            </>
                        )}

                        <div className="dropdown-divider"></div>
                        <Link
                            href={linkRoute('tenant.notifications.index') + '?markAsRead=1'}
                            className="dropdown-item dropdown-footer"
                        >
                            {__('tenant.view_all_notifications')}
                        </Link>
                    </div>
                </li>

                <li className="nav-item dropdown">
                    <a
                        href="#"
                        className="nav-link dropdown-toggle"
                        data-toggle="dropdown"
                        aria-expanded="false"
                    >
                        <span>
                            <i className='mr-2 fas fa-building text-sm'></i>
                        </span>
                        <span className='text-sm'>
                            {currentBranch ? currentBranch.name : "Select Branch"}
                        </span>
                    </a>
                    <ul className="dropdown-menu">
                        {(auth.user.branches ?? []).map((item, index) => (
                            <li key={index}>
                                <button
                                    className={`dropdown-item text-sm ${
                                        item.id === auth.user.currentBranch?.id ? "active" : ""
                                    }`}
                                    onClick={() => handleSelect(item)} // Update label on click
                                >
                                    <span>
                                        <i className='mr-2 fas fa-building text-sm'></i>
                                    </span>
                                    <span className='text-sm'>{item.name}</span>
                                </button>
                            </li>
                        ))}
                    </ul>
                </li>

                <li className="nav-item dropdown">
                    <a
                        className="nav-link mt-1"
                        href="#"
                        id="userDropdown"
                        data-toggle="dropdown"
                        aria-haspopup="true"
                        aria-expanded="false"
                        style={{display: "flex", alignItems: "center"}}
                    >
                        <img
                            src={asset('dashboard/dist/images/avatar.png')}
                            alt="User Avatar"
                            className="rounded-circle"
                            style={{width: "20px", height: "20px"}}
                        />
                        <span
                            className="d-none d-md-inline ml-2 text-sm">{capitalizeFirstLetter(auth.user?.name)}</span>
                    </a>
                    <div

                        className={classNames('dropdown-menu dropdown-menu-lg dropdown-menu-right shadow', {
                            'dropdown-menu-right': (locale === 'en'),
                            'dropdown-menu-left': (locale === 'ar'),
                        })}
                        aria-labelledby="userDropdown"
                        style={{width: "340px", borderRadius: "10px"}}
                    >
                        {/* Header Section */}
                        <div className="text-center p-4 border-bottom bg-light">
                            <img
                                src={asset('dashboard/dist/images/avatar.png')}
                                alt="User Avatar"
                                className="rounded-circle mb-2"
                                style={{width: "50px", height: "50px", objectFit: "cover"}}
                            />
                            <h6 className="font-weight-bold mb-0">{capitalizeFirstLetter(auth.user?.name)}</h6>
                            <p className="text-muted mb-0" style={{fontSize: "0.85rem"}}>
                                <span>
                                    {Array.isArray(auth.user?.roles) && auth.user.roles.length
                                        ? auth.user.roles.map((r) => capitalizeFirstLetter(String(r))).join(', ')
                                        : ''}
                                </span>
                            </p>
                        </div>
                        {/* Action Links with Enhanced Hover */}
                        <div>
                            <Link
                                href={route('profile.edit')}
                                className="dropdown-item d-flex align-items-center"
                                style={{
                                    height: '50px',
                                    padding: '0 15px',
                                    color: '#212529',
                                    transition: 'background-color 0.3s ease, color 0.3s ease',
                                }}
                                onMouseEnter={(e) => {
                                    e.target.style.backgroundColor = '#f8f9fa';
                                    e.target.style.color = '#495057';
                                }}
                                onMouseLeave={(e) => {
                                    e.target.style.backgroundColor = 'transparent';
                                    e.target.style.color = '#212529';
                                }}
                            >
                                <i className="fas fa-user mr-2"></i>
                                <span>{__('tenant.profile')}</span>
                            </Link>
                            <Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                className="dropdown-item d-flex align-items-center border-0 bg-transparent w-100 text-left"
                                style={{
                                    height: '50px',
                                    padding: '0 15px',
                                    color: '#212529',
                                    transition: 'background-color 0.3s ease, color 0.3s ease',
                                }}
                                onMouseEnter={(e) => {
                                    e.target.style.backgroundColor = '#f8f9fa';
                                    e.target.style.color = '#495057';
                                }}
                                onMouseLeave={(e) => {
                                    e.target.style.backgroundColor = 'transparent';
                                    e.target.style.color = '#212529';
                                }}
                            >
                                <i className="fas fa-sign-out-alt mr-2"></i>
                                <span>{__('tenant.logout')}</span>
                            </Link>
                        </div>
                    </div>
                </li>

                <li className="nav-item">
                    <a className="nav-link" data-widget="fullscreen" href="#" role="button">
                        <i className="fas fa-expand-arrows-alt mx-1"></i>
                    </a>
                </li>
            </ul>
        </nav>
    )
}

export default Navbar
