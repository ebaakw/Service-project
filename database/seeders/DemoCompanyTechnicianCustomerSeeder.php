<?php

namespace Database\Seeders;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\DiscountScope;
use App\Enums\DiscountType;
use App\Enums\PriceType;
use App\Enums\SocialLoginProvider;
use App\Enums\SubscriptionStatus;
use App\Enums\TechnicianAvailabilityStatus;
use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\CompanyPriceItem;
use App\Models\CompanyPriceList;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Discount;
use App\Models\ServiceCategory;
use App\Models\SocialAccount;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoCompanyTechnicianCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->updateOrCreate(
            ['slug' => 'demo-services-co'],
            [
                'name' => 'إباء للحلول الحاسوبية المتكاملة',
                'name_ar' => 'إباء للحلول الحاسوبية المتكاملة',
                'name_en' => 'Ebaa Integrated Computer Solutions',
                'status' => CompanyStatus::Active,
                'subscription_status' => SubscriptionStatus::Active,
                'subscription_starts_at' => now(),
                'rating_average' => 4.6,
                'rating_count' => 48,
                'default_inspection_fee' => 5.000,
                'logo_path' => null,
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

        $acRoot = ServiceCategory::query()->where('slug', 'ac')->first();
        $pricingCategoryIds = [];
        if ($acRoot !== null) {
            $pricingCategoryIds = ServiceCategory::query()
                ->where(function ($q) use ($acRoot): void {
                    $q->where('parent_id', $acRoot->getKey())
                        ->orWhere('id', $acRoot->getKey());
                })
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('id')
                ->all();
        }

        $categoryIds = $acRoot !== null
            ? [(int) $acRoot->getKey()]
            : [];

        if ($categoryIds === []) {
            $categoryIds = ServiceCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->limit(3)
                ->pluck('id')
                ->all();
            $pricingCategoryIds = $categoryIds;
        }

        $this->seedDemoPricing($company, $pricingCategoryIds !== [] ? $pricingCategoryIds : $categoryIds);

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

        $demoTechnicians = [
            [
                'email' => 'technician-demo@example.test',
                'employee_code' => 'TEC-001',
                'civil_id_number' => '288010101001',
                'first_name_ar' => 'أحمد',
                'second_name_ar' => 'خالد',
                'family_name_ar' => 'العتيبي',
                'first_name_en' => 'Ahmed',
                'second_name_en' => 'Khaled',
                'family_name_en' => 'Alotaibi',
                'availability_status' => TechnicianAvailabilityStatus::Available,
                'lat' => '29.3782000',
                'lng' => '47.9901000',
                'rating_average' => 4.5,
                'rating_count' => 15,
                'avatar' => 'https://ui-avatars.com/api/?name=Ahmed+Alotaibi&background=0D6EFD&color=fff&size=128',
                'favorite' => true,
            ],
            [
                'email' => 'technician-demo-2@example.test',
                'employee_code' => 'TEC-002',
                'civil_id_number' => '288010101002',
                'first_name_ar' => 'سارة',
                'second_name_ar' => 'فهد',
                'family_name_ar' => 'الشمري',
                'first_name_en' => 'Sara',
                'second_name_en' => 'Fahad',
                'family_name_en' => 'Alshammari',
                'availability_status' => TechnicianAvailabilityStatus::Available,
                'lat' => '29.3705000',
                'lng' => '47.9688000',
                'rating_average' => 4.8,
                'rating_count' => 22,
                'avatar' => 'https://ui-avatars.com/api/?name=Sara+Alshammari&background=198754&color=fff&size=128',
                'favorite' => true,
            ],
            [
                'email' => 'technician-demo-3@example.test',
                'employee_code' => 'TEC-003',
                'civil_id_number' => '288010101003',
                'first_name_ar' => 'يوسف',
                'second_name_ar' => 'منصور',
                'family_name_ar' => 'العتيبي',
                'first_name_en' => 'Yousef',
                'second_name_en' => 'Mansour',
                'family_name_en' => 'Alotaibi',
                'availability_status' => TechnicianAvailabilityStatus::Available,
                'lat' => '29.3651000',
                'lng' => '47.9852000',
                'rating_average' => 4.2,
                'rating_count' => 8,
                'avatar' => 'https://ui-avatars.com/api/?name=Yousef+Alotaibi&background=6610f2&color=fff&size=128',
                'favorite' => true,
            ],
            [
                'email' => 'technician-demo-4@example.test',
                'employee_code' => 'TEC-004',
                'civil_id_number' => '288010101004',
                'first_name_ar' => 'نورة',
                'second_name_ar' => 'سعد',
                'family_name_ar' => 'الرشيدي',
                'first_name_en' => 'Noura',
                'second_name_en' => 'Saad',
                'family_name_en' => 'Alrashidi',
                'availability_status' => TechnicianAvailabilityStatus::Available,
                'lat' => '29.3829000',
                'lng' => '47.9604000',
                'rating_average' => 3.9,
                'rating_count' => 11,
                'avatar' => 'https://ui-avatars.com/api/?name=Noura+Alrashidi&background=fd7e14&color=fff&size=128',
                'favorite' => false,
            ],
            [
                'email' => 'technician-demo-5@example.test',
                'employee_code' => 'TEC-005',
                'civil_id_number' => '288010101005',
                'first_name_ar' => 'خالد',
                'second_name_ar' => 'علي',
                'family_name_ar' => 'المطيري',
                'first_name_en' => 'Khaled',
                'second_name_en' => 'Ali',
                'family_name_en' => 'Almutairi',
                'availability_status' => TechnicianAvailabilityStatus::Busy,
                'lat' => '29.3884000',
                'lng' => '47.9727000',
                'rating_average' => 4.1,
                'rating_count' => 19,
                'avatar' => 'https://ui-avatars.com/api/?name=Khaled+Almutairi&background=ffc107&color=212529&size=128',
                'favorite' => false,
            ],
            [
                'email' => 'technician-demo-6@example.test',
                'employee_code' => 'TEC-006',
                'civil_id_number' => '288010101006',
                'first_name_ar' => 'فاطمة',
                'second_name_ar' => 'حسن',
                'family_name_ar' => 'العنزي',
                'first_name_en' => 'Fatima',
                'second_name_en' => 'Hassan',
                'family_name_en' => 'Alanazi',
                'availability_status' => TechnicianAvailabilityStatus::Busy,
                'lat' => '29.3608000',
                'lng' => '47.9749000',
                'rating_average' => 4.6,
                'rating_count' => 27,
                'avatar' => 'https://ui-avatars.com/api/?name=Fatima+Alanazi&background=dc3545&color=fff&size=128',
                'favorite' => false,
            ],
            [
                'email' => 'technician-demo-7@example.test',
                'employee_code' => 'TEC-007',
                'civil_id_number' => '288010101007',
                'first_name_ar' => 'ماجد',
                'second_name_ar' => 'إبراهيم',
                'family_name_ar' => 'الظفيري',
                'first_name_en' => 'Majed',
                'second_name_en' => 'Ibrahim',
                'family_name_en' => 'Althufairi',
                'availability_status' => TechnicianAvailabilityStatus::Offline,
                'lat' => '29.3552000',
                'lng' => '47.9915000',
                'rating_average' => 3.7,
                'rating_count' => 5,
                'avatar' => 'https://ui-avatars.com/api/?name=Majed+Althufairi&background=6c757d&color=fff&size=128',
                'favorite' => false,
            ],
            [
                'email' => 'technician-demo-8@example.test',
                'employee_code' => 'TEC-008',
                'civil_id_number' => '288010101008',
                'first_name_ar' => 'هند',
                'second_name_ar' => 'ناصر',
                'family_name_ar' => 'السبيعي',
                'first_name_en' => 'Hind',
                'second_name_en' => 'Nasser',
                'family_name_en' => 'Alsubaie',
                'availability_status' => TechnicianAvailabilityStatus::Offline,
                'lat' => '29.3921000',
                'lng' => '47.9556000',
                'rating_average' => 4.0,
                'rating_count' => 13,
                'avatar' => 'https://ui-avatars.com/api/?name=Hind+Alsubaie&background=20c997&color=fff&size=128',
                'favorite' => false,
            ],
        ];

        $favoriteIds = [];

        foreach ($demoTechnicians as $demo) {
            $displayNameAr = Technician::composeFullName(
                $demo['first_name_ar'],
                $demo['second_name_ar'],
                $demo['family_name_ar'],
            );
            $displayNameEn = Technician::composeFullName(
                $demo['first_name_en'],
                $demo['second_name_en'],
                $demo['family_name_en'],
            );

            $technicianUser = User::query()->updateOrCreate(
                ['email' => $demo['email']],
                [
                    'name' => $displayNameEn,
                    'name_ar' => $displayNameAr,
                    'name_en' => $displayNameEn,
                    'password' => 'password',
                    'type' => UserType::Technician,
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                    'avatar_path' => $demo['avatar'],
                ]
            );

            $technician = Technician::query()->updateOrCreate(
                ['user_id' => $technicianUser->getKey()],
                [
                    'company_id' => $company->getKey(),
                    'first_name_ar' => $demo['first_name_ar'],
                    'second_name_ar' => $demo['second_name_ar'],
                    'family_name_ar' => $demo['family_name_ar'],
                    'first_name_en' => $demo['first_name_en'],
                    'second_name_en' => $demo['second_name_en'],
                    'family_name_en' => $demo['family_name_en'],
                    'display_name_ar' => $displayNameAr,
                    'display_name_en' => $displayNameEn,
                    'civil_id_number' => $demo['civil_id_number'],
                    'employee_code' => $demo['employee_code'],
                    'job_title' => 'Field technician',
                    'job_title_ar' => 'فني ميداني',
                    'job_title_en' => 'Field technician',
                    'is_active' => true,
                    'review_status' => TechnicianReviewStatus::Approved,
                    'reviewed_at' => now(),
                    'availability_status' => $demo['availability_status'],
                    'current_lat' => $demo['lat'],
                    'current_lng' => $demo['lng'],
                    'last_location_at' => now(),
                    'rating_average' => $demo['rating_average'],
                    'rating_count' => $demo['rating_count'],
                    'completed_orders_count' => max(3, (int) $demo['rating_count']),
                ]
            );

            if ($categoryIds !== []) {
                $technician->serviceCategories()->syncWithoutDetaching($categoryIds);
            }

            if ($demo['favorite'] === true) {
                $favoriteIds[] = $technician->getKey();
            }
        }

        if ($favoriteIds !== []) {
            $customer->favoriteTechnicians()->syncWithoutDetaching($favoriteIds);
        }
    }

    /**
     * @param  list<int>  $categoryIds
     */
    private function seedDemoPricing(Company $company, array $categoryIds): void
    {
        if ($categoryIds === []) {
            return;
        }

        Discount::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'name_ar' => 'يوجد خصم 20%',
            ],
            [
                'name' => 'يوجد خصم 20%',
                'name_en' => '20% discount available',
                'description' => 'خصم 20% على قائمة الأسعار',
                'description_ar' => 'خصم 20% على قائمة الأسعار',
                'description_en' => '20% off the price list',
                'discount_type' => DiscountType::Percentage,
                'discount_value' => 20,
                'discount_scope' => DiscountScope::FullMenu,
                'starts_at' => null,
                'ends_at' => null,
                'is_active' => true,
                'used_count' => 0,
            ]
        );

        $priceList = CompanyPriceList::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'name_ar' => 'قائمة الأسعار',
            ],
            [
                'name' => 'قائمة الأسعار',
                'name_en' => 'Price List',
                'description' => 'قائمة أسعار خدمات التكييف التجريبية',
                'description_ar' => 'قائمة أسعار خدمات التكييف التجريبية',
                'description_en' => 'Demo AC services price list',
                'is_default' => true,
                'starts_at' => null,
                'ends_at' => null,
                'discount_type' => null,
                'discount_value' => null,
                'is_active' => true,
            ]
        );

        $priceList->serviceCategories()->sync($categoryIds);

        $catalog = [
            ['name_ar' => 'كمبريسر 2.5 طن وكالة', 'name_en' => 'Compressor 2.5 ton original', 'price' => '100.000'],
            ['name_ar' => 'كمبريسر 1.5 طن وكالة', 'name_en' => 'Compressor 1.5 ton original', 'price' => '85.000'],
            ['name_ar' => 'لوحة تحكم إلكترونية', 'name_en' => 'Electronic control board', 'price' => '45.000'],
            ['name_ar' => 'مروحة خارجية', 'name_en' => 'Outdoor fan motor', 'price' => '35.000'],
            ['name_ar' => 'غاز فريون R410', 'name_en' => 'R410 refrigerant gas', 'price' => '25.000'],
            ['name_ar' => 'صيانة دورية وحدة سبليت', 'name_en' => 'Split unit periodic maintenance', 'price' => '20.000'],
            ['name_ar' => 'غسيل وحدة داخلية', 'name_en' => 'Indoor unit cleaning', 'price' => '15.000'],
            ['name_ar' => 'تركيب حامل خارجي', 'name_en' => 'Outdoor bracket installation', 'price' => '30.000'],
            ['name_ar' => 'تبديل ترموستات', 'name_en' => 'Thermostat replacement', 'price' => '18.000'],
        ];

        foreach ($categoryIds as $categoryId) {
            foreach ($catalog as $index => $row) {
                CompanyPriceItem::query()->updateOrCreate(
                    [
                        'company_price_list_id' => $priceList->getKey(),
                        'service_category_id' => $categoryId,
                        'name_ar' => $row['name_ar'],
                    ],
                    [
                        'company_id' => $company->getKey(),
                        'service_problem_id' => null,
                        'name_en' => $row['name_en'],
                        'description_ar' => null,
                        'description_en' => null,
                        'price_type' => PriceType::Fixed,
                        'price' => $row['price'],
                        'minimum_price' => null,
                        'discount_type' => null,
                        'discount_value' => null,
                        'inspection_fee' => '5.000',
                        'warranty_days' => 90,
                        'warranty_description_ar' => 'ضمان 90 يوم',
                        'warranty_description_en' => '90-day warranty',
                        'duration_minutes' => 60,
                        'sort_order' => $index,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
