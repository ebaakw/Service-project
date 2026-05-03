<?php

namespace App\Models;

use App\Enums\SettingValueType;
use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasLocalizedFields;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'label_ar',
        'label_en',
        'description_ar',
        'description_en',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'type' => SettingValueType::class,
            'is_public' => 'boolean',
            'value' => 'json',
        ];
    }
}
