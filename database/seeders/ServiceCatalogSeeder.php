<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use App\Models\ServiceProblem;
use Illuminate\Database\Seeder;

class ServiceCatalogSeeder extends Seeder
{
    /**
     * Placeholder roots that duplicate the curated icon catalog (ids 1–8).
     *
     * @var list<string>
     */
    private const PLACEHOLDER_ROOT_SLUGS = [
        'electricity',
        'plumbing',
        'refrigerators',
        'decoration',
    ];

    public function run(): void
    {
        ServiceCategory::query()
            ->whereNull('parent_id')
            ->whereIn('slug', self::PLACEHOLDER_ROOT_SLUGS)
            ->update(['is_active' => false]);

        $this->seedAcHierarchy();
        $this->seedLeafRootCatalog();
    }

    private function seedAcHierarchy(): void
    {
        $ac = $this->upsertRootCategory([
            'slug' => 'ac',
            'name_ar' => 'تكييف',
            'name_en' => 'AC',
            'sort_order' => 0,
        ]);

        $children = [
            [
                'slug' => 'ac-central',
                'name_ar' => 'تكييف مركزي',
                'name_en' => 'Central AC',
                'sort_order' => 0,
            ],
            [
                'slug' => 'ac-units',
                'name_ar' => 'وحدات',
                'name_en' => 'Split / Units',
                'sort_order' => 1,
            ],
            [
                'slug' => 'ac-ventilation',
                'name_ar' => 'تهوية',
                'name_en' => 'Ventilation',
                'sort_order' => 2,
            ],
        ];

        $childModels = [];
        foreach ($children as $child) {
            $childModels[$child['slug']] = ServiceCategory::query()->updateOrCreate(
                ['slug' => $child['slug']],
                [
                    'parent_id' => $ac->getKey(),
                    'name_ar' => $child['name_ar'],
                    'name_en' => $child['name_en'],
                    'sort_order' => $child['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        // Legacy root-level AC problems: deactivate so mobile uses leaf categories.
        ServiceProblem::query()
            ->where('service_category_id', $ac->getKey())
            ->update(['is_active' => false]);

        $problemsByChild = [
            'ac-central' => [
                $this->problem('لا يبرد', 'Not cooling', 'التكييف المركزي يعمل لكن الهواء غير بارد كفاية.', 'Central AC is running but air is not cold enough.', 0),
                $this->problem('تسريب مياه', 'Water leaking', 'يوجد تسريب مياه من وحدات التكييف المركزي.', 'Water is leaking from the central AC units.', 1),
                $this->otherProblem(2),
            ],
            'ac-units' => [
                $this->problem('لا يبرد', 'Not cooling', 'وحدة التكييف لا تبرّد الغرفة كما يجب.', 'The AC unit is not cooling the room properly.', 0),
                $this->problem('صوت عالي', 'Loud noise', 'الوحدة تصدر صوتاً غير طبيعي أثناء التشغيل.', 'The unit makes unusual noise while running.', 1),
                $this->otherProblem(2),
            ],
            'ac-ventilation' => [
                $this->problem('ضعف التهوية', 'Weak airflow', 'تدفق الهواء ضعيف أو غير كافٍ.', 'Airflow is weak or insufficient.', 0),
                $this->problem('رائحة كريهة', 'Bad odor', 'تخرج رائحة غير مستحبة من فتحات التهوية.', 'Unpleasant odor coming from the vents.', 1),
                $this->otherProblem(2),
            ],
        ];

        foreach ($problemsByChild as $slug => $problems) {
            $this->syncProblems($childModels[$slug], $problems);
        }
    }

    private function seedLeafRootCatalog(): void
    {
        $catalog = [
            [
                'slug' => 'water-tanks',
                'name_ar' => 'خزانات',
                'name_en' => 'Water tanks',
                'sort_order' => 1,
                'problems' => [
                    $this->problem('تسريب خزان', 'Tank leaking', 'يوجد تسريب من الخزان أو التوصيلات.', 'The tank or its fittings are leaking.', 0),
                    $this->problem('تنظيف خزان', 'Tank cleaning', 'يحتاج الخزان إلى تنظيف أو تعقيم.', 'The tank needs cleaning or sanitizing.', 1),
                    $this->problem('مضخة لا تعمل', 'Pump not working', 'مضخة المياه لا تعمل أو ضعيفة.', 'The water pump is not working or is weak.', 2),
                    $this->otherProblem(3),
                ],
            ],
            [
                'slug' => 'sanitary',
                'name_ar' => 'صحي',
                'name_en' => 'Sanitary / Plumbing',
                'sort_order' => 2,
                'problems' => [
                    $this->problem('انسداد مجاري', 'Clogged drain', 'انسداد في المغسلة أو المجاري.', 'Sink or drain is clogged.', 0),
                    $this->problem('تسريب مياه', 'Water leak', 'تسريب من الأنابيب أو الخلاطات.', 'Leak from pipes or faucets.', 1),
                    $this->problem('مشكلة مرحاض', 'Toilet issue', 'المرحاض لا يعمل أو يتسرب.', 'Toilet is not working or leaking.', 2),
                    $this->otherProblem(3),
                ],
            ],
            [
                'slug' => 'electrical',
                'name_ar' => 'كهربائي',
                'name_en' => 'Electrical',
                'sort_order' => 3,
                'problems' => [
                    $this->problem('انقطاع كهرباء', 'Power outage', 'انقطاع جزئي أو كامل للكهرباء.', 'Partial or full power outage.', 0),
                    $this->problem('قواطع تفصل', 'Breaker trips', 'القواطع تفصل بشكل متكرر.', 'Circuit breakers trip repeatedly.', 1),
                    $this->problem('مشكلة إنارة', 'Lighting issue', 'أعطال في الإضاءة أو المفاتيح.', 'Lighting or switch problems.', 2),
                    $this->otherProblem(3),
                ],
            ],
            [
                'slug' => 'elevators',
                'name_ar' => 'مصاعد',
                'name_en' => 'Elevators',
                'sort_order' => 4,
                'problems' => [
                    $this->problem('المصعد متوقف', 'Elevator stuck', 'المصعد لا يتحرك أو عالق بين الطوابق.', 'Elevator is not moving or stuck between floors.', 0),
                    $this->problem('أبواب لا تغلق', 'Doors not closing', 'أبواب المصعد لا تغلق أو لا تفتح بشكل صحيح.', 'Elevator doors do not close or open properly.', 1),
                    $this->problem('صوت غير طبيعي', 'Unusual noise', 'أصوات غير طبيعية أثناء التشغيل.', 'Unusual noises while operating.', 2),
                    $this->otherProblem(3),
                ],
            ],
            [
                'slug' => 'satellite',
                'name_ar' => 'ستلايت',
                'name_en' => 'Satellite',
                'sort_order' => 5,
                'problems' => [
                    $this->problem('لا توجد إشارة', 'No signal', 'الشاشة تظهر عدم وجود إشارة.', 'Screen shows no satellite signal.', 0),
                    $this->problem('ضبط طبق', 'Dish alignment', 'يحتاج الطبق إلى ضبط أو توجيه.', 'Satellite dish needs alignment.', 1),
                    $this->problem('مشكلة ريسيفر', 'Receiver issue', 'الجهاز لا يعمل أو لا يفتح القنوات.', 'Receiver is not working or channels fail to open.', 2),
                    $this->otherProblem(3),
                ],
            ],
            [
                'slug' => 'carpentry',
                'name_ar' => 'نجار',
                'name_en' => 'Carpentry',
                'sort_order' => 6,
                'problems' => [
                    $this->problem('باب لا يغلق', 'Door not closing', 'الباب لا يغلق أو المفصلات تالفة.', 'Door will not close or hinges are damaged.', 0),
                    $this->problem('خزائن / درف', 'Cabinets / drawers', 'إصلاح خزائن أو درف أو مقابض.', 'Repair cabinets, drawers, or handles.', 1),
                    $this->problem('أعمال خشبية', 'Woodwork', 'تركيب أو إصلاح أعمال خشبية عامة.', 'General woodwork installation or repair.', 2),
                    $this->otherProblem(3),
                ],
            ],
            [
                'slug' => 'cameras',
                'name_ar' => 'كاميرات',
                'name_en' => 'Cameras / CCTV',
                'sort_order' => 7,
                'problems' => [
                    $this->problem('كاميرا لا تعمل', 'Camera offline', 'كاميرا أو أكثر لا تعرض الصورة.', 'One or more cameras are not showing video.', 0),
                    $this->problem('تسجيل لا يعمل', 'Recording failed', 'جهاز التسجيل لا يحفظ الفيديو.', 'DVR/NVR is not recording.', 1),
                    $this->problem('تركيب / إضافة كاميرا', 'Install / add camera', 'طلب تركيب أو إضافة كاميرا جديدة.', 'Request to install or add a new camera.', 2),
                    $this->otherProblem(3),
                ],
            ],
        ];

        foreach ($catalog as $item) {
            $root = $this->upsertRootCategory([
                'slug' => $item['slug'],
                'name_ar' => $item['name_ar'],
                'name_en' => $item['name_en'],
                'sort_order' => $item['sort_order'],
            ]);

            // Leaf roots must not gain placeholder children; mobile skips subtype bar when [].
            $this->syncProblems($root, $item['problems']);
        }
    }

    /**
     * Match existing curated roots by slug or Arabic name so we do not duplicate
     * icon-bearing rows that were created from the platform UI.
     *
     * @param  array{slug: string, name_ar: string, name_en: string, sort_order: int}  $attrs
     */
    private function upsertRootCategory(array $attrs): ServiceCategory
    {
        $existing = ServiceCategory::query()
            ->whereNull('parent_id')
            ->where(function ($query) use ($attrs): void {
                $query->where('slug', $attrs['slug'])
                    ->orWhere('name_ar', $attrs['name_ar']);
            })
            ->orderBy('id')
            ->first();

        if ($existing !== null) {
            $existing->fill([
                'name_ar' => $attrs['name_ar'],
                'name_en' => $attrs['name_en'],
                'sort_order' => $attrs['sort_order'],
                'is_active' => true,
                'parent_id' => null,
            ])->save();

            return $existing->refresh();
        }

        return ServiceCategory::query()->create([
            'slug' => $attrs['slug'],
            'parent_id' => null,
            'name_ar' => $attrs['name_ar'],
            'name_en' => $attrs['name_en'],
            'sort_order' => $attrs['sort_order'],
            'is_active' => true,
        ]);
    }

    /**
     * @param  list<array{title_ar: string, title_en: string, description_ar: string, description_en: string, is_other: bool, sort_order: int}>  $problems
     */
    private function syncProblems(ServiceCategory $category, array $problems): void
    {
        foreach ($problems as $problem) {
            ServiceProblem::query()->updateOrCreate(
                [
                    'service_category_id' => $category->getKey(),
                    'title_ar' => $problem['title_ar'],
                ],
                [
                    'title_en' => $problem['title_en'],
                    'description_ar' => $problem['description_ar'],
                    'description_en' => $problem['description_en'],
                    'icon_path' => null,
                    'is_other' => $problem['is_other'],
                    'sort_order' => $problem['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * @return array{title_ar: string, title_en: string, description_ar: string, description_en: string, is_other: bool, sort_order: int}
     */
    private function problem(
        string $titleAr,
        string $titleEn,
        string $descriptionAr,
        string $descriptionEn,
        int $sortOrder
    ): array {
        return [
            'title_ar' => $titleAr,
            'title_en' => $titleEn,
            'description_ar' => $descriptionAr,
            'description_en' => $descriptionEn,
            'is_other' => false,
            'sort_order' => $sortOrder,
        ];
    }

    /**
     * @return array{title_ar: string, title_en: string, description_ar: string, description_en: string, is_other: bool, sort_order: int}
     */
    private function otherProblem(int $sortOrder): array
    {
        return [
            'title_ar' => 'أخرى',
            'title_en' => 'Other',
            'description_ar' => 'مشكلة أخرى غير مذكورة في القائمة.',
            'description_en' => 'Another problem not listed above.',
            'is_other' => true,
            'sort_order' => $sortOrder,
        ];
    }
}
