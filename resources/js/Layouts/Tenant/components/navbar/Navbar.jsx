import classNames from "classnames";
import config from "@/Config/config.js";
import {__} from "@/Utility/Hooks/useTranslate.jsx";
import {Link, usePage} from "@inertiajs/react";
import {asset} from "@/Utility/Hooks/useAsset.jsx";
import {useEffect, useState} from "react";

const Navbar = ({shellVariant}) => {
    const {locale, today, auth, timezone} = usePage().props;
    const tz = timezone || 'UTC';

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

    const changeLang = (event) => {
        event.preventDefault();
        const lang = locale === 'en' ? 'ar' : 'en';
        window.location.href = route('locale.switch', { locale: lang });
    }

    const capitalizeFirstLetter = (val) => {
        return String(val).charAt(0).toUpperCase() + String(val).slice(1);
    }

    const shellBadge = shellVariant === 'platform'
        ? __('dashboard.shell.platform_badge')
        : shellVariant === 'company'
            ? __('dashboard.shell.company_badge')
            : null;

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
            </ul>

            <ul className="navbar-nav ml-auto">
                {shellBadge && (
                    <li className="nav-item d-none d-md-inline-block text-sm mt-2 px-2">
                        <span className="badge badge-light text-dark">
                            {shellBadge}
                        </span>
                    </li>
                )}

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
                            src={asset('adminlte/dist/images/avatar.png')}
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
                        <div className="text-center p-4 border-bottom bg-light">
                            <img
                                src={asset('adminlte/dist/images/avatar.png')}
                                alt="User Avatar"
                                className="rounded-circle mb-2"
                                style={{width: "50px", height: "50px", objectFit: "cover"}}
                            />
                            <h6 className="font-weight-bold mb-0">{capitalizeFirstLetter(auth.user?.name)}</h6>
                            <p className="text-muted mb-0" style={{fontSize: "0.85rem"}}>
                                <span>
                                    {shellBadge}
                                </span>
                            </p>
                        </div>
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
