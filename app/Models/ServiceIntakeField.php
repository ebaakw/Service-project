<?php

namespace App\Models;

use App\Enums\FieldType;
use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceIntakeField extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceIntakeFieldFactory> */
    use HasFactory, HasLocalizedFields, SoftDeletes;

    protected $fillable = [
        'service_category_id',
        'service_problem_id',
        'label_ar',
        'label_en',
        'field_type',
        'options',
        'placeholder_ar',
        'placeholder_en',
        'is_required',
        'sort_order',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saving(function (ServiceIntakeField $field): void {
            if (! is_array($field->options) || $field->options === []) {
                return;
            }

            $normalized = [];
            foreach ($field->options as $opt) {
                if (! is_array($opt)) {
                    continue;
                }
                $value = $opt['value'] ?? $opt['label'] ?? null;
                if ($value === null) {
                    continue;
                }
                $labelAr = $opt['label_ar'] ?? $opt['label'] ?? (string) $value;
                $labelEn = $opt['label_en'] ?? $opt['label'] ?? (string) $value;
                $normalized[] = [
                    'value' => (string) $value,
                    'label_ar' => is_string($labelAr) ? $labelAr : (string) $value,
                    'label_en' => is_string($labelEn) ? $labelEn : (string) $value,
                ];
            }
            $field->options = $normalized;
        });
    }

    protected function casts(): array
    {
        return [
            'field_type' => FieldType::class,
            'options' => 'array',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function problem(): BelongsTo
    {
        return $this->belongsTo(ServiceProblem::class, 'service_problem_id');
    }
}
