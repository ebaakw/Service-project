<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            PlatformAdminSeeder::class,
            PlatformSettingsSeeder::class,
            ServiceCatalogSeeder::class,
            ContentPagesSeeder::class,
            DemoCompanyTechnicianCustomerSeeder::class,
            DemoTechnicianQueueOrdersSeeder::class,
        ]);
    }
}
