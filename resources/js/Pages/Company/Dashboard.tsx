import CompanyDashboardLayout from '@/Layouts/CompanyDashboardLayout';
import { Head } from '@inertiajs/react';

export default function CompanyDashboard() {
    return (
        <CompanyDashboardLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Company dashboard
                </h2>
            }
        >
            <Head title="Company dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            Company workspace (placeholder).
                        </div>
                    </div>
                </div>
            </div>
        </CompanyDashboardLayout>
    );
}
