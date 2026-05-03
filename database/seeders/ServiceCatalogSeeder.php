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

        $problems = [
            ['title_ar' => 'لا يبرد', 'title_en' => 'Not cooling', 'is_other' => false, 'sort_order' => 0],
            ['title_ar' => 'حار', 'title_en' => 'Hot air', 'is_other' => false, 'sort_order' => 1],
            ['title_ar' => 'يخر ماي', 'title_en' => 'Water leaking', 'is_other' => false, 'sort_order' => 2],
            ['title_ar' => 'صوت عالي', 'title_en' => 'Loud noise', 'is_other' => false, 'sort_order' => 3],
            ['title_ar' => 'أخرى', 'title_en' => 'Other', 'is_other' => true, 'sort_order' => 4],
        ];

        foreach ($problems as $p) {
            ServiceProblem::query()->updateOrCreate(
                [
                    'service_category_id' => $ac->getKey(),
                    'title_ar' => $p['title_ar'],
                ],
                [
                    'title_en' => $p['title_en'],
                    'is_other' => $p['is_other'],
                    'sort_order' => $p['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
