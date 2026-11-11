<?php

namespace Database\Seeders;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\DiscountScope;
use App\Enums\DiscountType;
use App\Enums\PortfolioProjectReviewStatus;
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
use App\Models\CompanyReview;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Discount;
use App\Models\Media;
use App\Models\PortfolioProject;
use App\Models\ServiceCategory;
use App\Models\SocialAccount;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DemoCompanyTechnicianCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->updateOrCreate(
            ['slug' => 'demo-services-co'],
            [
                'name' => 'شركة إباء للحلول الحاسوبية',
                'name_ar' => 'شركة إباء للحلول الحاسوبية',
                'name_en' => 'Ebaa Computer Solutions',
                'short_description' => 'شركة متخصصة في أعمال التكييف و التهوية',
                'short_description_ar' => 'شركة متخصصة في أعمال التكييف و التهوية',
                'short_description_en' => 'Specialized company for AC and ventilation works',
                'description' => 'نقدم خدمات صيانة وتركيب التكييف والسباكة والخزانات بفريق فنيين معتمدين.',
                'description_ar' => 'نقدم خدمات صيانة وتركيب التكييف والسباكة والخزانات بفريق فنيين معتمدين.',
                'description_en' => 'We provide AC, plumbing, and water-tank maintenance with certified technicians.',
                'warranty_note_ar' => 'الشركة تعطي كفالة تصل إلى 6 شهور على بعض قطع الغيار',
                'warranty_note_en' => 'The company provides up to 6 months warranty on selected spare parts',
                'status' => CompanyStatus::Active,
                'subscription_status' => SubscriptionStatus::Active,
                'subscription_starts_at' => now(),
                'is_featured' => true,
                'rating_average' => 4.5,
                'rating_count' => 15,
                'default_inspection_fee' => 5.000,
                'logo_path' => 'https://picsum.photos/seed/ebaa-demo-logo/200/200',
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

        $catalogCategories = ServiceCategory::query()
            ->whereIn('slug', [
                'ac',
                'ac-units',
                'ac-central',
                'ac-ventilation',
                'water-tanks',
                'sanitary',
                'electrical',
                'elevators',
                'satellite',
                'cameras',
                'carpentry',
            ])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('slug');

        $acRoot = $catalogCategories->get('ac');
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

        $categoryIds = $catalogCategories
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

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
        /** @var list<Technician> $seededTechnicians */
        $seededTechnicians = [];

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

            $seededTechnicians[] = $technician;

            if ($demo['favorite'] === true) {
                $favoriteIds[] = $technician->getKey();
            }
        }

        if ($favoriteIds !== []) {
            $customer->favoriteTechnicians()->syncWithoutDetaching($favoriteIds);
        }

        $customer->favoriteCompanies()->syncWithoutDetaching([$company->getKey()]);

        $this->seedDemoPortfolio($company, $seededTechnicians, $catalogCategories);
        $this->seedDemoCompanyReviews($company, $customer);
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
            ['name_ar' => 'كمبريسر 2.5 طن وكالة', 'name_en' => 'Compressor 2.5 ton original', 'price' => '100.000', 'duration' => 120, 'warranty' => 180],
            ['name_ar' => 'كمبريسر 1.5 طن وكالة', 'name_en' => 'Compressor 1.5 ton original', 'price' => '85.000', 'duration' => 90, 'warranty' => 180],
            ['name_ar' => 'لوحة تحكم إلكترونية', 'name_en' => 'Electronic control board', 'price' => '45.000', 'duration' => 60, 'warranty' => 90],
            ['name_ar' => 'مروحة خارجية', 'name_en' => 'Outdoor fan motor', 'price' => '35.000', 'duration' => 45, 'warranty' => 90],
            ['name_ar' => 'غاز فريون R410', 'name_en' => 'R410 refrigerant gas', 'price' => '25.000', 'duration' => 40, 'warranty' => 30],
            ['name_ar' => 'صيانة دورية وحدة سبليت', 'name_en' => 'Split unit periodic maintenance', 'price' => '20.000', 'duration' => 60, 'warranty' => null],
            ['name_ar' => 'غسيل وحدة داخلية', 'name_en' => 'Indoor unit cleaning', 'price' => '15.000', 'duration' => 45, 'warranty' => null],
            ['name_ar' => 'تركيب حامل خارجي', 'name_en' => 'Outdoor bracket installation', 'price' => '30.000', 'duration' => 75, 'warranty' => 60],
            ['name_ar' => 'تبديل ترموستات', 'name_en' => 'Thermostat replacement', 'price' => '18.000', 'duration' => 30, 'warranty' => 90],
            ['name_ar' => 'فحص وتشخيص أعطال', 'name_en' => 'Fault diagnosis inspection', 'price' => '12.000', 'duration' => 30, 'warranty' => null],
            ['name_ar' => 'تنظيف مجاري الهواء', 'name_en' => 'Air duct cleaning', 'price' => '40.000', 'duration' => 90, 'warranty' => 30],
        ];

        // Seed a representative set once against the first category to keep the public price-list readable.
        $primaryCategoryId = (int) $categoryIds[0];

        foreach ($catalog as $index => $row) {
            CompanyPriceItem::query()->updateOrCreate(
                [
                    'company_price_list_id' => $priceList->getKey(),
                    'service_category_id' => $primaryCategoryId,
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
                    'warranty_days' => $row['warranty'],
                    'warranty_description_ar' => $row['warranty'] !== null ? 'ضمان '.$row['warranty'].' يوم' : null,
                    'warranty_description_en' => $row['warranty'] !== null ? $row['warranty'].'-day warranty' : null,
                    'duration_minutes' => $row['duration'],
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * @param  list<Technician>  $technicians
     * @param  Collection<string, ServiceCategory>|iterable<int|string, ServiceCategory|int>  $categories
     */
    private function seedDemoPortfolio(Company $company, array $technicians, iterable $categories): void
    {
        if ($technicians === []) {
            return;
        }

        $categoryBySlug = collect($categories)
            ->map(function ($category): ?ServiceCategory {
                if ($category instanceof ServiceCategory) {
                    return $category;
                }

                return ServiceCategory::query()->find($category);
            })
            ->filter()
            ->keyBy(fn (ServiceCategory $category): string => (string) $category->slug);

        if ($categoryBySlug->isEmpty()) {
            $categoryBySlug = ServiceCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->keyBy('slug');
        }

        if ($categoryBySlug->isEmpty()) {
            return;
        }

        $fallbackCategory = $categoryBySlug->first();

        $projects = [
            [
                'title_ar' => 'تركيب تكييف سبليت غرفة معيشة',
                'title_en' => 'Living room split AC install',
                'category_slugs' => ['ac-units', 'ac'],
                'image_theme' => 'ac',
                'featured' => true,
                'location_ar' => 'السالمية',
            ],
            [
                'title_ar' => 'صيانة وحدة تكييف مركزية',
                'title_en' => 'Central AC unit maintenance',
                'category_slugs' => ['ac-central', 'ac'],
                'image_theme' => 'ac',
                'featured' => true,
                'location_ar' => 'حولي',
            ],
            [
                'title_ar' => 'تعبئة فريون وإصلاح تسريب',
                'title_en' => 'Refrigerant refill and leak fix',
                'category_slugs' => ['ac', 'ac-units'],
                'image_theme' => 'ac',
                'featured' => false,
                'location_ar' => 'الفروانية',
            ],
            [
                'title_ar' => 'تنظيف خزان مياه علوي',
                'title_en' => 'Rooftop water tank cleaning',
                'category_slugs' => ['water-tanks'],
                'image_theme' => 'water-tanks',
                'featured' => true,
                'location_ar' => 'الجابرية',
            ],
            [
                'title_ar' => 'استبدال مضخة خزان',
                'title_en' => 'Water tank pump replacement',
                'category_slugs' => ['water-tanks'],
                'image_theme' => 'water-tanks',
                'featured' => false,
                'location_ar' => 'الرقعي',
            ],
            [
                'title_ar' => 'إصلاح تسريب أنابيب صحي',
                'title_en' => 'Plumbing pipe leak repair',
                'category_slugs' => ['sanitary'],
                'image_theme' => 'sanitary',
                'featured' => false,
                'location_ar' => 'بيان',
            ],
            [
                'title_ar' => 'فتح انسداد مجاري مطبخ',
                'title_en' => 'Kitchen drain unclogging',
                'category_slugs' => ['sanitary'],
                'image_theme' => 'sanitary',
                'featured' => false,
                'location_ar' => 'الشامية',
            ],
            [
                'title_ar' => 'تركيب وحدات تكييف مكتبية',
                'title_en' => 'Office AC units installation',
                'category_slugs' => ['ac-units', 'ac'],
                'image_theme' => 'ac',
                'featured' => true,
                'location_ar' => 'الشرق',
            ],
            [
                'title_ar' => 'صيانة دورية لشبكة التهوية',
                'title_en' => 'Ventilation network service',
                'category_slugs' => ['ac-ventilation', 'ac'],
                'image_theme' => 'ventilation',
                'featured' => false,
                'location_ar' => 'الجهراء',
            ],
            [
                'title_ar' => 'تغيير كمبريسر 2.5 طن',
                'title_en' => '2.5 ton compressor replacement',
                'category_slugs' => ['ac', 'ac-central'],
                'image_theme' => 'ac',
                'featured' => true,
                'location_ar' => 'العديلية',
            ],
            [
                'title_ar' => 'تركيب خلاطات وأدوات صحية',
                'title_en' => 'Faucet and sanitary fixtures',
                'category_slugs' => ['sanitary'],
                'image_theme' => 'sanitary',
                'featured' => false,
                'location_ar' => 'كيفان',
            ],
            [
                'title_ar' => 'عزل خزان أرضي ضد التسريب',
                'title_en' => 'Ground tank waterproofing',
                'category_slugs' => ['water-tanks'],
                'image_theme' => 'water-tanks',
                'featured' => false,
                'location_ar' => 'الصباحية',
            ],
            [
                'title_ar' => 'صيانة لوحة كهرباء منزلية',
                'title_en' => 'Home electrical panel service',
                'category_slugs' => ['electrical'],
                'image_theme' => 'electrical',
                'featured' => true,
                'location_ar' => 'الروضة',
            ],
            [
                'title_ar' => 'إصلاح أعطال مصعد سكني',
                'title_en' => 'Residential elevator repair',
                'category_slugs' => ['elevators'],
                'image_theme' => 'elevators',
                'featured' => false,
                'location_ar' => 'المرقاب',
            ],
            [
                'title_ar' => 'ضبط طبق ستلايت وإصلاح الإشارة',
                'title_en' => 'Satellite dish alignment',
                'category_slugs' => ['satellite'],
                'image_theme' => 'satellite',
                'featured' => false,
                'location_ar' => 'الفنطاس',
            ],
            [
                'title_ar' => 'تركيب كاميرات مراقبة CCTV',
                'title_en' => 'CCTV camera installation',
                'category_slugs' => ['cameras'],
                'image_theme' => 'cameras',
                'featured' => true,
                'location_ar' => 'السلام',
            ],
            [
                'title_ar' => 'إصلاح أبواب وخزائن خشبية',
                'title_en' => 'Door and cabinet carpentry',
                'category_slugs' => ['carpentry'],
                'image_theme' => 'carpentry',
                'featured' => false,
                'location_ar' => 'الأحمدي',
            ],
        ];

        foreach ($projects as $index => $row) {
            $category = $fallbackCategory;
            foreach ($row['category_slugs'] as $slug) {
                if ($categoryBySlug->has($slug)) {
                    $category = $categoryBySlug->get($slug);
                    break;
                }
            }

            $technician = $technicians[$index % count($technicians)];
            $imageUrls = $this->thematicPortfolioImages($row['image_theme'], $index);

            $project = PortfolioProject::query()->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'title_ar' => $row['title_ar'],
                ],
                [
                    'service_category_id' => $category->getKey(),
                    'technician_id' => $technician->getKey(),
                    'title_en' => $row['title_en'],
                    'description_ar' => 'عمل منفذ بواسطة فريق شركة إباء للحلول الحاسوبية في '.$row['location_ar'].'.',
                    'description_en' => 'Completed project by Ebaa Computer Solutions team in '.$row['location_ar'].'.',
                    'location' => $row['location_ar'],
                    'location_ar' => $row['location_ar'],
                    'location_en' => $row['location_ar'],
                    'completed_at' => now()->subDays($index + 1),
                    'is_featured' => $row['featured'],
                    'is_active' => true,
                    'review_status' => PortfolioProjectReviewStatus::Approved,
                    'reviewed_at' => now()->subDays($index),
                    'rejection_reason' => null,
                ]
            );

            $project->technicians()->sync([$technician->getKey()]);

            foreach ($imageUrls as $sortOrder => $imageUrl) {
                Media::query()->updateOrCreate(
                    [
                        'model_type' => PortfolioProject::class,
                        'model_id' => $project->getKey(),
                        'collection_name' => 'portfolio',
                        'sort_order' => $sortOrder,
                    ],
                    [
                        'disk' => 'public',
                        'file_path' => $imageUrl,
                        'file_name' => $row['image_theme'].'-'.($index + 1).'-'.($sortOrder + 1).'.jpg',
                        'mime_type' => 'image/jpeg',
                        'size' => 140000 + ($index * 1000) + ($sortOrder * 500),
                    ]
                );
            }
        }
    }

    /**
     * Thematic Wikimedia Commons images keyed by service activity.
     *
     * @return list<string>
     */
    private function thematicPortfolioImages(string $theme, int $index): array
    {
        $catalog = [
            'ac' => [
                $this->commonsImage('Air_conditioner.jpg'),
                $this->commonsImage('Window_air_conditioner.jpg'),
                $this->commonsImage('DAIKIN AIR CONDITIONER OUTDOOR UNIT & Panasonic AIR CONDITIONER OUTDOOR UNIT.jpg'),
                $this->commonsImage('Panasonic AIR CONDITIONER INDOOR UNIT CS-C10KJ2 (2).jpg'),
            ],
            'ventilation' => [
                $this->commonsImage('HVAC.jpg'),
                $this->commonsImage('Air_duct.jpg'),
            ],
            'water-tanks' => [
                $this->commonsImage('Blue plastic water tank 2.jpg'),
                $this->commonsImage('Water Tank Kerala House.JPG'),
                $this->commonsImage('Water_tank.jpg'),
                $this->commonsImage('Water_tower.jpg'),
            ],
            'sanitary' => [
                $this->commonsImage('Plumbing pipes.jpg'),
                $this->commonsImage('Plumbing.jpg'),
                $this->commonsImage('Bathroom_sink.jpg'),
                $this->commonsImage('Water_tap.jpg'),
            ],
            'electrical' => [
                $this->commonsImage('Electrical_panel.jpg'),
                $this->commonsImage('Circuit_breaker.jpg'),
            ],
            'elevators' => [
                $this->commonsImage('Elevator.jpg'),
                $this->commonsImage('Buenos Aires - Art Nouveau elevator - 7028.jpg'),
            ],
            'satellite' => [
                $this->commonsImage('Akash DTH Satellite dish.jpg'),
                $this->commonsImage('Satellite dish in Austria.JPG'),
            ],
            'cameras' => [
                $this->commonsImage('Security_camera.jpg'),
                $this->commonsImage('CCTV.jpg'),
            ],
            'carpentry' => [
                $this->commonsImage('Carpentry.jpg'),
                $this->commonsImage('Woodwork.jpg'),
            ],
        ];

        $pool = $catalog[$theme] ?? $catalog['ac'];
        $primary = $pool[$index % count($pool)];
        $secondary = $pool[($index + 1) % count($pool)];

        return array_values(array_unique([$primary, $secondary]));
    }

    private function commonsImage(string $filename): string
    {
        return 'https://commons.wikimedia.org/wiki/Special:FilePath/'.rawurlencode($filename).'?width=800';
    }

    private function seedDemoCompanyReviews(Company $company, Customer $customer): void
    {
        $reviews = [
            [
                'rating' => 5,
                'author_name' => 'فاطمة العلي',
                'comment_ar' => 'خدمة ممتازة والفني التزم بالموعد. الشركة محترفة جداً.',
                'comment_en' => 'Excellent service and the technician arrived on time. Very professional company.',
                'with_image' => true,
            ],
            [
                'rating' => 4,
                'author_name' => 'خالد المنصور',
                'comment_ar' => 'جودة العمل عالية والأسعار واضحة. أنصح بالتعامل معهم.',
                'comment_en' => 'High work quality and clear pricing. I recommend them.',
                'with_image' => false,
            ],
            [
                'rating' => 5,
                'author_name' => 'نورة السالم',
                'comment_ar' => 'تم إصلاح التكييف بسرعة والكفالة مطمئنة.',
                'comment_en' => 'AC fixed quickly and the warranty gave peace of mind.',
                'with_image' => true,
            ],
            [
                'rating' => 4,
                'author_name' => 'Demo Customer',
                'comment_ar' => 'تجربة جيدة من تطبيق الموبايل حتى انتهاء العمل.',
                'comment_en' => 'Good experience from the mobile app through to job completion.',
                'customer_id' => $customer->getKey(),
                'with_image' => false,
            ],
            [
                'rating' => 3,
                'author_name' => 'عبدالله الراشد',
                'comment_ar' => 'العمل مقبول لكن التأخير بسيط عن الموعد.',
                'comment_en' => 'Acceptable work but a slight delay versus the appointment.',
                'with_image' => false,
            ],
        ];

        foreach ($reviews as $index => $row) {
            $review = CompanyReview::query()->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'author_name' => $row['author_name'],
                    'comment_ar' => $row['comment_ar'],
                ],
                [
                    'customer_id' => $row['customer_id'] ?? null,
                    'rating' => $row['rating'],
                    'comment' => $row['comment_ar'],
                    'comment_en' => $row['comment_en'],
                    'is_published' => true,
                    'created_at' => now()->subDays($index + 1),
                    'updated_at' => now()->subDays($index + 1),
                ]
            );

            if (($row['with_image'] ?? false) === true) {
                Media::query()->updateOrCreate(
                    [
                        'model_type' => CompanyReview::class,
                        'model_id' => $review->getKey(),
                        'collection_name' => 'review',
                        'sort_order' => 0,
                    ],
                    [
                        'disk' => 'public',
                        'file_path' => 'https://picsum.photos/seed/company-review-'.($index + 1).'/640/480',
                        'file_name' => 'review-'.($index + 1).'.jpg',
                        'mime_type' => 'image/jpeg',
                        'size' => 90000 + ($index * 1000),
                    ]
                );
            }
        }

        $stats = CompanyReview::query()
            ->where('company_id', $company->getKey())
            ->where('is_published', true)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total')
            ->first();

        $company->forceFill([
            'rating_average' => round((float) ($stats->avg_rating ?? 4.5), 2),
            'rating_count' => (int) ($stats->total ?? 0),
        ])->save();
    }
}
