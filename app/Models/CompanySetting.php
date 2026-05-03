<?php

namespace App\Models;

use App\Enums\SettingValueType;
use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySetting extends Model
{
    use HasLocalizedFields;

    protected $fillable = [
        'company_id',
        'key',
        'value',
        'type',
        'group',
        'description',
        'label_ar',
        'label_en',
        'description_ar',
        'description_en',
    ];

    protected function casts(): array
    {
        return [
            'type' => SettingValueType::class,
            'value' => 'json',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
