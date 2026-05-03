<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'model_type' => Company::class,
            'model_id' => Company::factory(),
            'collection_name' => 'logo',
            'disk' => 'public',
            'file_path' => 'companies/1/logo/test.jpg',
            'file_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'sort_order' => 0,
        ];
    }
}
