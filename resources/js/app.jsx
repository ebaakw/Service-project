import HtmlLocale from '@/Components/HtmlLocale';
import { createInertiaApp, router } from '@inertiajs/react';
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
    resolve: async (name) => {
        const tsxGlob = import.meta.glob('./Pages/**/*.tsx');
        const jsxGlob = import.meta.glob('./Pages/**/*.jsx');
        const tsxPath = `./Pages/${name}.tsx`;
        const jsxPath = `./Pages/${name}.jsx`;
        if (tsxGlob[tsxPath]) {
            const module = await tsxGlob[tsxPath]();

            const Component = module.default;
            const Wrapped = (props) => (
                <Fragment>
                    <HtmlLocale />
                    <Component {...props} />
                </Fragment>
            );

            return Wrapped;
        }
        if (jsxGlob[jsxPath]) {
            const module = await jsxGlob[jsxPath]();

            const Component = module.default;
            const Wrapped = (props) => (
                <Fragment>
                    <HtmlLocale />
                    <Component {...props} />
                </Fragment>
            );

            return Wrapped;
        }

        throw new Error(`Inertia page not found: ./Pages/${name}.tsx or .jsx`);
    },
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(<App {...props} />);
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
