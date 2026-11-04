<?php

namespace Database\Factories;

use App\Enums\BannerLinkType;
use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'title_ar' => 'تخفيض 20% على جميع الخدمات',
            'title_en' => '20% off all services',
            'subtitle_ar' => null,
            'subtitle_en' => null,
            'cta_label_ar' => 'احصل عليه الآن',
            'cta_label_en' => 'Get it now',
            'image_path' => null,
            'link_type' => BannerLinkType::None,
            'link_value' => null,
            'sort_order' => 0,
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }
}
