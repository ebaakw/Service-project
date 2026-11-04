<?php

namespace Database\Seeders;

use App\Models\ContentPage;
use Illuminate\Database\Seeder;

class ContentPagesSeeder extends Seeder
{
    public function run(): void
    {
        ContentPage::query()->updateOrCreate(
            ['slug' => 'guarantees'],
            [
                'title_ar' => 'الضمانات',
                'title_en' => 'Guarantees',
                'body_ar' => "نضمن جودة الخدمة ورضا العميل.\nفي حال عدم الرضا يمكنك فتح شكوى عبر التطبيق وسنتابعها مع الشركة.",
                'body_en' => "We guarantee service quality and customer satisfaction.\nIf you are not satisfied, you can open a complaint in the app and we will follow up with the company.",
                'is_active' => true,
            ],
        );
    }
}
