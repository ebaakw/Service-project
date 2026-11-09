<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use App\Models\ServiceProblem;
use Illuminate\Database\Seeder;

class ServiceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name_ar' => 'تكييف', 'name_en' => 'AC', 'slug' => 'ac', 'sort_order' => 0],
            ['name_ar' => 'كهرباء', 'name_en' => 'Electricity', 'slug' => 'electricity', 'sort_order' => 1],
            ['name_ar' => 'سباكة', 'name_en' => 'Plumbing', 'slug' => 'plumbing', 'sort_order' => 2],
            ['name_ar' => 'ثلاجات', 'name_en' => 'Refrigerators', 'slug' => 'refrigerators', 'sort_order' => 3],
            ['name_ar' => 'ديكور', 'name_en' => 'Decoration', 'slug' => 'decoration', 'sort_order' => 4],
        ];

        foreach ($categories as $row) {
            ServiceCategory::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'parent_id' => null,
                    'name_ar' => $row['name_ar'],
                    'name_en' => $row['name_en'],
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        $ac = ServiceCategory::query()->where('slug', 'ac')->first();
        if ($ac === null) {
            return;
        }

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
                [
                    'title_ar' => 'لا يبرد',
                    'title_en' => 'Not cooling',
                    'description_ar' => 'التكييف المركزي يعمل لكن الهواء غير بارد كفاية.',
                    'description_en' => 'Central AC is running but air is not cold enough.',
                    'is_other' => false,
                    'sort_order' => 0,
                ],
                [
                    'title_ar' => 'تسريب مياه',
                    'title_en' => 'Water leaking',
                    'description_ar' => 'يوجد تسريب مياه من وحدات التكييف المركزي.',
                    'description_en' => 'Water is leaking from the central AC units.',
                    'is_other' => false,
                    'sort_order' => 1,
                ],
                [
                    'title_ar' => 'أخرى',
                    'title_en' => 'Other',
                    'description_ar' => 'مشكلة أخرى غير مذكورة في القائمة.',
                    'description_en' => 'Another problem not listed above.',
                    'is_other' => true,
                    'sort_order' => 2,
                ],
            ],
            'ac-units' => [
                [
                    'title_ar' => 'لا يبرد',
                    'title_en' => 'Not cooling',
                    'description_ar' => 'وحدة التكييف لا تبرّد الغرفة كما يجب.',
                    'description_en' => 'The AC unit is not cooling the room properly.',
                    'is_other' => false,
                    'sort_order' => 0,
                ],
                [
                    'title_ar' => 'صوت عالي',
                    'title_en' => 'Loud noise',
                    'description_ar' => 'الوحدة تصدر صوتاً غير طبيعي أثناء التشغيل.',
                    'description_en' => 'The unit makes unusual noise while running.',
                    'is_other' => false,
                    'sort_order' => 1,
                ],
                [
                    'title_ar' => 'أخرى',
                    'title_en' => 'Other',
                    'description_ar' => 'مشكلة أخرى غير مذكورة في القائمة.',
                    'description_en' => 'Another problem not listed above.',
                    'is_other' => true,
                    'sort_order' => 2,
                ],
            ],
            'ac-ventilation' => [
                [
                    'title_ar' => 'ضعف التهوية',
                    'title_en' => 'Weak airflow',
                    'description_ar' => 'تدفق الهواء ضعيف أو غير كافٍ.',
                    'description_en' => 'Airflow is weak or insufficient.',
                    'is_other' => false,
                    'sort_order' => 0,
                ],
                [
                    'title_ar' => 'رائحة كريهة',
                    'title_en' => 'Bad odor',
                    'description_ar' => 'تخرج رائحة غير مستحبة من فتحات التهوية.',
                    'description_en' => 'Unpleasant odor coming from the vents.',
                    'is_other' => false,
                    'sort_order' => 1,
                ],
                [
                    'title_ar' => 'أخرى',
                    'title_en' => 'Other',
                    'description_ar' => 'مشكلة أخرى غير مذكورة في القائمة.',
                    'description_en' => 'Another problem not listed above.',
                    'is_other' => true,
                    'sort_order' => 2,
                ],
            ],
        ];

        foreach ($problemsByChild as $slug => $problems) {
            $category = $childModels[$slug] ?? null;
            if ($category === null) {
                continue;
            }

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
    }
}
