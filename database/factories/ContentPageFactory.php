<?php

namespace Database\Factories;

use App\Models\ContentPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContentPage>
 */
class ContentPageFactory extends Factory
{
    protected $model = ContentPage::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'ulid' => (string) Str::ulid(),
            'slug' => $slug,
            'title_ar' => 'عنوان الصفحة',
            'title_en' => 'Page title',
            'body_ar' => 'محتوى الصفحة بالعربية.',
            'body_en' => 'Page body in English.',
            'is_active' => true,
        ];
    }
}
