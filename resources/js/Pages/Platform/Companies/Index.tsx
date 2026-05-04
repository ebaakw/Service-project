import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { Head } from '@inertiajs/react';

export default function PlatformCompaniesIndex() {
    return (
        <PlatformAdminLayout
            breadcrumbKey="dashboard.nav.companies"
            header={
                <h1 className="m-0 text-2xl font-weight-bold text-dark">Companies</h1>
            }
        >
            <Head title="Companies" />

            <div className="card card-outline card-primary">
                <div className="card-body">
                    <p className="text-muted mb-0">
                        Platform companies list (placeholder). Full listing will load from the API in a
                        later iteration.
                    </p>
                </div>
            </div>
        </PlatformAdminLayout>
    );
}
