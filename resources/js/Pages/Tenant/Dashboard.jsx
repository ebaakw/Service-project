import MainLayout from '@/Layouts/Tenant/MainLayout.jsx';
import { __ } from '@/Utility/Hooks/useTranslate.jsx';

export default function Dashboard() {
    return (
        <MainLayout title="tenant.dashboard" name="tenant.dashboard">
            <div className="row">
                <div className="col-lg-6">
                    <div className="card card-primary card-outline">
                        <div className="card-header">
                            <h3 className="card-title">{__('tenant.dashboard')}</h3>
                        </div>
                        <div className="card-body">
                            <p className="mb-0">
                                {__('tenant.more_info')}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </MainLayout>
    );
}
