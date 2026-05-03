export interface User {
    id: number;
    name: string;
    name_ar?: string | null;
    name_en?: string | null;
    email: string;
    email_verified_at?: string;
}

export type DashboardT = {
    nav?: Record<string, string>;
    actions?: Record<string, string>;
    forms?: Record<string, string>;
    orders?: Record<string, string>;
    service_categories?: Record<string, string>;
    status?: Record<string, string>;
};

export type EnumLabels = {
    order_status?: Record<string, string>;
    technician_availability?: Record<string, string>;
    invoice_status?: Record<string, string>;
    payment_status?: Record<string, string>;
    company_status?: Record<string, string>;
    subscription_status?: Record<string, string>;
    price_type?: Record<string, string>;
};

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: (User & Record<string, unknown>) | null;
    };
    locale: string;
    direction: 'rtl' | 'ltr';
    t?: DashboardT;
    enumLabels?: EnumLabels;
};
