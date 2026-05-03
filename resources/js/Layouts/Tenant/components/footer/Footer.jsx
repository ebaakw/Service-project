import './print.css'
import {__} from "@/Utility/Hooks/useTranslate.jsx";
const Footer = () => {
    return (
        <>
            <footer className="main-footer hidden-print">
                <strong> &copy;  {new Date().getFullYear()}  <a href="/"> {__('tenant.ebaa_system')} </a>.</strong>
                {__('tenant.all_rights_reserved')}.
                <div className="float-right d-none d-sm-inline-block">
                    <b>{__('tenant.version')}</b> 2.1.0


                </div>
            </footer>
        </>
    )
}


export default Footer
