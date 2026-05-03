import MainLayout from '@/Layouts/Tenant/MainLayout.jsx';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';

export default function Placeholder({ routeName, titleKey }) {
    const heading = titleKey ? __(titleKey) : (routeName || '');

    return (
        <MainLayout title={titleKey || 'tenant.dashboard'} name={titleKey || 'tenant.dashboard'}>
            <div className="card card-secondary card-outline">
                <div className="card-header">
                    <h3 className="card-title">{heading}</h3>
                </div>
                <div className="card-body">
                    {routeName && (
                        <p className="text-muted mb-0">
                            Route: <code>{routeName}</code>
                        </p>
                    )}
                    <p className="mb-0 mt-2">{__('tenant.more_info')}</p>
                </div>
            </div>
        </MainLayout>
    );
}
