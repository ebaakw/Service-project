export const __ = (key, replacements = {}) => {
    let translation = window._translations[key] || key;

    const keys = Object.keys(replacements).sort((a, b) => b.length - a.length);
    keys.forEach((r) => {
        const value = replacements[r];
        translation = translation.replaceAll(`:${r}`, value ?? '');
    });

    return translation;
}
