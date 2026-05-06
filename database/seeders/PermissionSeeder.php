<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // NOTE: Keep legacy keys (e.g. roles.manage, pricing.manage) for backwards compatibility.
        // New keys are more explicit (platform.* / company.*) but we do not remove old ones.
        $definitions = [
            // ---- Platform (dashboard + entities) ----
            ['key' => 'platform.dashboard.view', 'name' => 'View platform dashboard', 'group' => 'platform'],

            ['key' => 'companies.view', 'name' => 'View companies', 'group' => 'companies'],
            ['key' => 'companies.create', 'name' => 'Create companies', 'group' => 'companies'],
            ['key' => 'companies.update', 'name' => 'Update companies', 'group' => 'companies'],
            ['key' => 'companies.delete', 'name' => 'Delete companies', 'group' => 'companies'],

            ['key' => 'users.view', 'name' => 'View users', 'group' => 'users'],
            ['key' => 'users.create', 'name' => 'Create users', 'group' => 'users'],
            ['key' => 'users.update', 'name' => 'Update users', 'group' => 'users'],
            ['key' => 'users.delete', 'name' => 'Delete users', 'group' => 'users'],

            ['key' => 'roles.view', 'name' => 'View roles', 'group' => 'roles'],
            ['key' => 'roles.create', 'name' => 'Create roles', 'group' => 'roles'],
            ['key' => 'roles.update', 'name' => 'Update roles', 'group' => 'roles'],
            ['key' => 'roles.delete', 'name' => 'Delete roles', 'group' => 'roles'],
            ['key' => 'roles.manage', 'name' => 'Manage roles', 'group' => 'roles'], // legacy umbrella

            ['key' => 'customers.view', 'name' => 'View customers', 'group' => 'customers'],

            ['key' => 'technicians.view', 'name' => 'View technicians', 'group' => 'technicians'],
            ['key' => 'technicians.create', 'name' => 'Create technicians', 'group' => 'technicians'],
            ['key' => 'technicians.update', 'name' => 'Update technicians', 'group' => 'technicians'],
            ['key' => 'technicians.delete', 'name' => 'Delete technicians', 'group' => 'technicians'],
            ['key' => 'technicians.reset_password', 'name' => 'Reset technician password', 'group' => 'technicians'],
            ['key' => 'technicians.manage', 'name' => 'Manage technicians', 'group' => 'technicians'], // legacy umbrella

            ['key' => 'services.manage', 'name' => 'Manage services', 'group' => 'services'],

            ['key' => 'orders.view', 'name' => 'View orders', 'group' => 'orders'],
            ['key' => 'orders.manage', 'name' => 'Manage orders', 'group' => 'orders'],
            ['key' => 'orders.assign_technician', 'name' => 'Assign technician to order', 'group' => 'orders'],
            ['key' => 'orders.update_status', 'name' => 'Update order status', 'group' => 'orders'],
            ['key' => 'orders.items.manage', 'name' => 'Manage order items', 'group' => 'orders'],
            ['key' => 'orders.send_invoice', 'name' => 'Send final invoice', 'group' => 'orders'],
            ['key' => 'orders.force_complete', 'name' => 'Force complete order', 'group' => 'orders'],

            ['key' => 'invoices.view', 'name' => 'View invoices', 'group' => 'invoices'],

            ['key' => 'settings.manage', 'name' => 'Manage settings', 'group' => 'settings'], // legacy umbrella

            ['key' => 'portfolio.manage', 'name' => 'Manage portfolio', 'group' => 'portfolio'], // legacy umbrella
            ['key' => 'complaints.manage', 'name' => 'Manage complaints', 'group' => 'complaints'], // legacy umbrella

            // ---- Company (explicit keys; still keep legacy keys above) ----
            ['key' => 'company.dashboard.view', 'name' => 'View company dashboard', 'group' => 'company'],
            ['key' => 'company.settings.view', 'name' => 'View company settings', 'group' => 'company'],
            ['key' => 'company.settings.update', 'name' => 'Update company settings', 'group' => 'company'],

            ['key' => 'company.users.view', 'name' => 'View company users', 'group' => 'company'],
            ['key' => 'company.users.create', 'name' => 'Create company users', 'group' => 'company'],
            ['key' => 'company.users.update', 'name' => 'Update company users', 'group' => 'company'],
            ['key' => 'company.users.delete', 'name' => 'Delete company users', 'group' => 'company'],

            ['key' => 'company.roles.view', 'name' => 'View company roles', 'group' => 'company'],
            ['key' => 'company.roles.create', 'name' => 'Create company roles', 'group' => 'company'],
            ['key' => 'company.roles.update', 'name' => 'Update company roles', 'group' => 'company'],
            ['key' => 'company.roles.delete', 'name' => 'Delete company roles', 'group' => 'company'],

            // Pricing (tenant area uses legacy pricing.manage today)
            ['key' => 'price_lists.view', 'name' => 'View price lists', 'group' => 'pricing'],
            ['key' => 'price_lists.create', 'name' => 'Create price lists', 'group' => 'pricing'],
            ['key' => 'price_lists.update', 'name' => 'Update price lists', 'group' => 'pricing'],
            ['key' => 'price_lists.delete', 'name' => 'Delete price lists', 'group' => 'pricing'],
            ['key' => 'price_items.view', 'name' => 'View price items', 'group' => 'pricing'],
            ['key' => 'price_items.create', 'name' => 'Create price items', 'group' => 'pricing'],
            ['key' => 'price_items.update', 'name' => 'Update price items', 'group' => 'pricing'],
            ['key' => 'price_items.delete', 'name' => 'Delete price items', 'group' => 'pricing'],
            ['key' => 'discounts.view', 'name' => 'View discounts', 'group' => 'pricing'],
            ['key' => 'discounts.create', 'name' => 'Create discounts', 'group' => 'pricing'],
            ['key' => 'discounts.update', 'name' => 'Update discounts', 'group' => 'pricing'],
            ['key' => 'discounts.delete', 'name' => 'Delete discounts', 'group' => 'pricing'],
            ['key' => 'pricing.manage', 'name' => 'Manage pricing', 'group' => 'pricing'], // legacy umbrella

            // Portfolio / Feedback / Invoices (company)
            ['key' => 'portfolio.view', 'name' => 'View portfolio projects', 'group' => 'portfolio'],
            ['key' => 'portfolio.create', 'name' => 'Create portfolio projects', 'group' => 'portfolio'],
            ['key' => 'portfolio.update', 'name' => 'Update portfolio projects', 'group' => 'portfolio'],
            ['key' => 'portfolio.delete', 'name' => 'Delete portfolio projects', 'group' => 'portfolio'],
            ['key' => 'portfolio.media.manage', 'name' => 'Manage portfolio media', 'group' => 'portfolio'],

            ['key' => 'feedback.view', 'name' => 'View feedback tickets', 'group' => 'feedback'],
            ['key' => 'feedback.reply', 'name' => 'Reply to feedback tickets', 'group' => 'feedback'],
            ['key' => 'feedback.update_status', 'name' => 'Update feedback ticket status', 'group' => 'feedback'],

            // keep existing report key
            ['key' => 'reports.view', 'name' => 'View reports', 'group' => 'reports'],
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
