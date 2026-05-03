<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            ['key' => 'companies.view', 'name' => 'View companies', 'group' => 'companies'],
            ['key' => 'companies.create', 'name' => 'Create companies', 'group' => 'companies'],
            ['key' => 'companies.update', 'name' => 'Update companies', 'group' => 'companies'],
            ['key' => 'companies.delete', 'name' => 'Delete companies', 'group' => 'companies'],
            ['key' => 'users.view', 'name' => 'View users', 'group' => 'users'],
            ['key' => 'users.create', 'name' => 'Create users', 'group' => 'users'],
            ['key' => 'users.update', 'name' => 'Update users', 'group' => 'users'],
            ['key' => 'roles.manage', 'name' => 'Manage roles', 'group' => 'roles'],
            ['key' => 'services.manage', 'name' => 'Manage services', 'group' => 'services'],
            ['key' => 'pricing.manage', 'name' => 'Manage pricing', 'group' => 'pricing'],
            ['key' => 'technicians.manage', 'name' => 'Manage technicians', 'group' => 'technicians'],
            ['key' => 'orders.view', 'name' => 'View orders', 'group' => 'orders'],
            ['key' => 'orders.manage', 'name' => 'Manage orders', 'group' => 'orders'],
            ['key' => 'invoices.view', 'name' => 'View invoices', 'group' => 'invoices'],
            ['key' => 'reports.view', 'name' => 'View reports', 'group' => 'reports'],
            ['key' => 'settings.manage', 'name' => 'Manage settings', 'group' => 'settings'],
            ['key' => 'portfolio.manage', 'name' => 'Manage portfolio', 'group' => 'portfolio'],
            ['key' => 'complaints.manage', 'name' => 'Manage complaints', 'group' => 'complaints'],
        ];

        foreach ($definitions as $def) {
            Permission::query()->updateOrCreate(
                ['key' => $def['key']],
                [
                    'name' => $def['name'],
                    'group' => $def['group'],
                    'description' => null,
                ]
            );
        }
    }
}
