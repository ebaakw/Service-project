<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceProblem extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceProblemFactory> */
    use HasFactory, HasLocalizedFields, SoftDeletes;

    protected $fillable = [
        'service_category_id',
        'title_ar',
        'title_en',
        'description_ar',
        'description_en',
        'is_other',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_other' => 'boolean',
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

    public function intakeFields(): HasMany
    {
        return $this->hasMany(ServiceIntakeField::class)->orderBy('sort_order');
    }

    public function priceItems(): HasMany
    {
        return $this->hasMany(CompanyPriceItem::class);
    }

    /**
     * @param  Builder<ServiceProblem>  $query
     * @return Builder<ServiceProblem>
     */
    public function scopeOther(Builder $query): Builder
    {
        return $query->where('is_other', true);
    }
}
