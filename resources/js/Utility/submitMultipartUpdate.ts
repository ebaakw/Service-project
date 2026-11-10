type SpoofMethod = 'put' | 'patch';

type InertiaFormLike = {
    // Inertia form transformers are typed to each form's data shape; keep this open.
    transform: (callback: (data: any) => any) => unknown;
    post: (url: string, options?: Record<string, unknown>) => void;
};

/**
 * Inertia v2 can send real PUT/PATCH FormData. PHP does not populate multipart
 * fields/files for those methods, so updates that include images/documents fail
 * in confusing ways (often a required text field looks empty).
 *
 * Always POST with `_method` spoofing for multipart updates.
 */
export function submitMultipartUpdate(
    form: InertiaFormLike,
    url: string,
    options: Record<string, unknown> = {},
    method: SpoofMethod = 'put',
): void {
    form.transform((data) => ({ ...data, _method: method }));
    form.post(url, {
        forceFormData: true,
        ...options,
    });
}

/**
 * Prefer focusing file inputs when a multipart update returns file-field errors.
 */
export function firstMultipartErrorKey(
    errors: Record<string, unknown> | undefined | null,
    fileFields: string[] = [],
): string | undefined {
    const keys = Object.keys(errors ?? {});
    if (keys.length === 0) return undefined;

    const fileKey = keys.find((key) => fileFields.some((field) => key === field || key.startsWith(`${field}.`)));
    if (!fileKey) return keys[0];

    return fileFields.find((field) => fileKey === field || fileKey.startsWith(`${field}.`)) ?? fileKey;
}
