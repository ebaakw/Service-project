import { useMemo } from 'react';
import { usePage } from '@inertiajs/react';

/**
 * Frontend feature gating from shared Inertia prop `plan_features` (array of strings).
 * When `plan_features` is omitted, all features are treated as enabled (shell / dev default).
 */
export function usePlanFeatures() {
    const { plan_features: rawPlanFeatures } = usePage().props || {};

    const set = useMemo(() => {
        if (rawPlanFeatures === undefined || rawPlanFeatures === null) {
            return null;
        }
        const arr = Array.isArray(rawPlanFeatures) ? rawPlanFeatures : [];

        return new Set(arr.map((v) => String(v)));
    }, [rawPlanFeatures]);

    const hasFeature = (key) => {
        if (set === null) {
            return true;
        }

        return set.has(String(key));
    };

    return {
        hasFeature,
        planFeatures: set === null ? [] : Array.from(set),
    };
}
