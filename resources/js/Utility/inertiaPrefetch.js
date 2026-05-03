import { ROUTE_TO_PAGE } from './inertiaRoutePageMap.js';

const pageModules = import.meta.glob('../Pages/**/*.jsx');

const prefetchedRoutes = new Set();

/**
 * Preload the Vite async chunk for an Inertia page when the user hovers a sidebar link.
 * Safe no-op if the route is unknown or already prefetched.
 */
export function prefetchInertiaPageForRoute(routeName) {
    if (!routeName || typeof routeName !== 'string') {
        return;
    }
    if (prefetchedRoutes.has(routeName)) {
        return;
    }
    const componentPath = ROUTE_TO_PAGE[routeName];
    if (!componentPath) {
        return;
    }
    const key = `../Pages/${componentPath}.jsx`;
    const loader = pageModules[key];
    if (typeof loader !== 'function') {
        return;
    }
    prefetchedRoutes.add(routeName);
    loader().catch(() => {});
}
