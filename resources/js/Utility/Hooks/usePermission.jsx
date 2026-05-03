import { usePage } from '@inertiajs/react';

export const hasRole = (role) => {
    const { auth } = usePage().props;
    if (!auth?.user?.roles) {
        return false;
    }

    return auth.user.roles.includes(role);
};

export const hasAnyRoles = (roles) => {
    const { auth } = usePage().props;
    if (!auth?.user?.roles) {
        return false;
    }

    return roles.some((role) => auth.user.roles.includes(role));
};

export const hasPermission = (permission) => {
    const { auth } = usePage().props;
    if (!auth?.user?.permissions) {
        return false;
    }
    if (auth.user.permissions.includes('*')) {
        return true;
    }

    return auth.user.permissions.includes(permission);
};

export const hasAnyPermissions = (permissions) => {
    const { auth } = usePage().props;
    if (!auth?.user?.permissions) {
        return false;
    }
    if (auth.user.permissions.includes('*')) {
        return true;
    }

    return permissions.some((permission) => auth.user.permissions.includes(permission));
};

export const hasAnyRolesOrPermissions = (roles, permissions) => {
    const { auth } = usePage().props;
    if (!auth?.user) {
        return false;
    }
    if (auth.user.permissions?.includes('*')) {
        return true;
    }

    return (
        roles.some((role) => auth.user.roles?.includes(role))
        || permissions.some((permission) => auth.user.permissions?.includes(permission))
    );
};

export const hasAnyRolesAndPermissions = (roles, permissions) => {
    const { auth } = usePage().props;
    if (!auth?.user) {
        return false;
    }
    if (auth.user.permissions?.includes('*')) {
        return true;
    }

    return (
        roles.some((role) => auth.user.roles?.includes(role))
        && permissions.some((permission) => auth.user.permissions?.includes(permission))
    );
};
