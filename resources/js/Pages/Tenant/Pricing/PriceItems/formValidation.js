export const REQUIRED_PRICE_ITEM_FIELDS = [
    'company_price_list_id',
    'service_category_id',
    'name_ar',
    'name_en',
    'price_type',
];

export function getRequiredFieldErrors(data, message) {
    return REQUIRED_PRICE_ITEM_FIELDS.reduce((errors, field) => {
        const value = data[field];

        if (value === null || value === undefined || value === '') {
            errors[field] = message;
        }

        return errors;
    }, {});
}

export function fieldError(field, serverErrors, clientErrors) {
    return clientErrors[field] || serverErrors[field];
}

export function invalidClass(baseClass, field, serverErrors, clientErrors) {
    return `${baseClass}${fieldError(field, serverErrors, clientErrors) ? ' is-invalid' : ''}`;
}

export function focusField(field) {
    window.requestAnimationFrame(() => {
        const element = document.querySelector(`[name="${field}"]`);

        if (!element) {
            return;
        }

        element.scrollIntoView({ block: 'center' });
        element.focus({ preventScroll: true });
    });
}

export function focusFirstError(errors) {
    const field = Object.keys(errors).find((key) => !key.startsWith('rows.'));

    if (field) {
        focusField(field);
    }
}
