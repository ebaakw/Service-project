<?php

namespace Database\Seeders;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\SocialLoginProvider;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\ServiceCategory;
use App\Models\SocialAccount;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoCompanyTechnicianCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->firstOrCreate(
            ['slug' => 'demo-services-co'],
            [
                'name' => 'Demo Services Co.',
                'status' => CompanyStatus::Active,
                'subscription_status' => SubscriptionStatus::Active,
                'subscription_starts_at' => now(),
            ]
        );

        $companyUser = User::query()->firstOrCreate(
            ['email' => 'company-demo@example.test'],
            [
                'name' => 'Company Demo User',
                'password' => 'password',
                'type' => UserType::CompanyUser,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]
        );

        CompanyMember::query()->firstOrCreate(
            [
                'company_id' => $company->getKey(),
                'user_id' => $companyUser->getKey(),
            ],
            [
                'role_id' => null,
                'status' => CompanyMemberStatus::Active,
                'joined_at' => now(),
            ]
        );

        $categoryIds = ServiceCategory::query()
            ->where('slug', 'ac')
            ->orderBy('sort_order')
            ->pluck('id')
            ->all();

        $technicianUser = User::query()->firstOrCreate(
            ['email' => 'technician-demo@example.test'],
            [
                'name' => 'Demo Technician',
                'password' => 'password',
                'type' => UserType::Technician,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]
        );

        $technician = Technician::query()->firstOrCreate(
            ['user_id' => $technicianUser->getKey()],
            [
                'company_id' => $company->getKey(),
                'first_name_ar' => 'فني',
                'second_name_ar' => 'تجريبي',
                'family_name_ar' => 'ديمو',
                'first_name_en' => 'Demo',
                'second_name_en' => 'Field',
                'family_name_en' => 'Technician',
                'civil_id_number' => '288010101001',
                'employee_code' => 'TEC-001',
                'job_title' => 'Field technician',
                'is_active' => true,
            ]
        );

        if ($categoryIds !== []) {
            $technician->serviceCategories()->syncWithoutDetaching($categoryIds);
        }

        $customerUser = User::query()->firstOrCreate(
            ['email' => 'customer-demo@example.test'],
            [
                'name' => 'Demo Customer',
                'password' => 'password',
                'type' => UserType::Customer,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]
        );

        $customer = Customer::query()->firstOrCreate(
            ['user_id' => $customerUser->getKey()],
            [
                'gender' => null,
                'notes' => 'Seeded demo customer.',
            ]
        );

        $address = CustomerAddress::query()->firstOrCreate(
            [
                'customer_id' => $customer->getKey(),
                'full_address' => 'Demo block, street 1',
            ],
            [
                'label' => 'Home',
                'contact_name' => $customerUser->name,
                'contact_phone' => $customerUser->phone ?? '+96550000000',
                'lat' => '29.3758590',
                'lng' => '47.9774050',
                'is_default' => true,
            ]
        );

        SocialAccount::query()->firstOrCreate(
            [
                'provider' => SocialLoginProvider::Google->value,
                'provider_user_id' => 'demo-google-sub',
            ],
            [
                'user_id' => $customerUser->getKey(),
                'email' => $customerUser->email,
                'avatar_url' => null,
                'token_metadata' => null,
            ]
        );

        if ($customer->default_address_id === null && $address->exists) {
            $customer->forceFill(['default_address_id' => $address->getKey()])->save();
        }
    }
}
