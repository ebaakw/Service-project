export interface User {
    id: number;
    ulid?: string | null;
    name: string;
    name_ar?: string | null;
    name_en?: string | null;
    email: string;
    phone?: string | null;
    email_verified_at?: string;
    type?: 'platform_admin' | 'company_user' | 'customer' | 'technician' | string | null;
    tenant?: string;
    permissions?: string[];
}

export type DashboardT = {
    nav?: Record<string, string>;
    actions?: Record<string, string>;
    forms?: Record<string, string>;
    orders?: Record<string, string>;
    service_categories?: Record<string, string>;
    status?: Record<string, string>;
    shell?: Record<string, string>;
};

export type EnumLabels = {
    order_status?: Record<string, string>;
    technician_availability?: Record<string, string>;
    technician_review_status?: Record<string, string>;
    invoice_status?: Record<string, string>;
    payment_status?: Record<string, string>;
    company_status?: Record<string, string>;
    subscription_status?: Record<string, string>;
    price_type?: Record<string, string>;
};

export type CurrentCompany = {
    id: number;
    ulid: string | null;
    display_name: string | null;
    logo_url: string | null;
    status: string | null;
    subscription_status: string | null;
};

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: (User & Record<string, unknown>) | null;
    };
    userType: User['type'] | null;
    currentCompany: CurrentCompany | null;
    locale: string;
    direction: 'rtl' | 'ltr';
    t?: DashboardT;
    enumLabels?: EnumLabels;
};
