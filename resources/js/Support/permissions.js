export function hasPermission(userPermissions, permission) {
    if (!permission) return true;
    const perms = Array.isArray(userPermissions) ? userPermissions : [];
    return perms.includes('*') || perms.includes(permission);
}

export function hasAnyPermission(userPermissions, permissions) {
    if (!permissions || permissions.length === 0) return true;
    const perms = Array.isArray(userPermissions) ? userPermissions : [];
    if (perms.includes('*')) return true;
    return permissions.some((p) => perms.includes(p));
}

export function canShowMenuItem(item, userPermissions) {
    if (!item) return false;
    if (item.bypassPermissionCheck === true) return true;

    if (item.permission) {
        return hasPermission(userPermissions, item.permission);
    }

    if (item.permissions) {
        return hasAnyPermission(userPermissions, item.permissions);
    }

    if (item.children && item.children.length > 0) {
        return item.children.some((child) => canShowMenuItem(child, userPermissions));
    }

    return true;
}

export function filterMenuByPermissions(menuItems, userPermissions) {
    const list = Array.isArray(menuItems) ? menuItems : [];

    return list
        .map((item) => {
            if (item?.children && Array.isArray(item.children)) {
                const children = item.children.filter((child) => canShowMenuItem(child, userPermissions));
                return { ...item, children };
            }
            return item;
        })
        .filter((item) => {
            if (!canShowMenuItem(item, userPermissions)) {
                return false;
            }

            if (item?.children && Array.isArray(item.children)) {
                return item.children.length > 0;
            }

            return true;
        });
}

