import HtmlLocale from '@/Components/HtmlLocale';
import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import axios from 'axios';
import { createRoot } from 'react-dom/client';
import { Fragment } from 'react';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';

axios.defaults.withCredentials = true;
axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

router.on('before', (visit) => {
    if (! visit) {
        return;
    }
    visit.headers = visit.headers || {};
    if (! visit.headers['X-Requested-With']) {
        visit.headers['X-Requested-With'] = 'XMLHttpRequest';
    }
    if (! visit.headers['Accept']) {
        visit.headers['Accept'] = 'application/json';
    }
});

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(
            <Fragment>
                <HtmlLocale />
                <App {...props} />
            </Fragment>,
        );
    },
    progress: {
        delay: 250,
        color: '#007bff',
        includeCSS: true,
        showSpinner: true,
    },
}).then(() => {});

router.on('invalid', (response) => {
    const status = response?.status;
    if (status === 419) {
        window.location.reload();
    }
});
