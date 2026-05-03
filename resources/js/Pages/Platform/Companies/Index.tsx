import PlatformAdminLayout from '@/Layouts/PlatformAdminLayout';
import { Head } from '@inertiajs/react';

export default function PlatformCompaniesIndex() {
    return (
        <PlatformAdminLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Companies
                </h2>
            }
        >
            <Head title="Companies" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            Platform companies list (placeholder). Full listing will load from the API in a
                            later iteration.
                        </div>
                    </div>
                </div>
            </div>
        </PlatformAdminLayout>
    );
}
