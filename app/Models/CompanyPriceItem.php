<?php

namespace App\Models;

use App\Enums\PriceType;
use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyPriceItem extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyPriceItemFactory> */
    use HasFactory, HasLocalizedFields, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (CompanyPriceItem $item): void {
            if ($item->company_price_list_id === null) {
                return;
            }

            $list = $item->relationLoaded('priceList')
                ? $item->priceList
                : CompanyPriceList::query()->find($item->company_price_list_id);

            if ($list !== null) {
                $item->company_id = $list->company_id;
            }
        });
    }

    protected $fillable = [
        'company_id',
        'company_price_list_id',
        'service_category_id',
        'service_problem_id',
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'price_type',
        'price',
        'minimum_price',
        'inspection_fee',
        'warranty_days',
        'warranty_description_ar',
        'warranty_description_en',
        'duration_minutes',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_type' => PriceType::class,
            'price' => 'decimal:3',
            'minimum_price' => 'decimal:3',
            'inspection_fee' => 'decimal:3',
            'warranty_days' => 'integer',
            'duration_minutes' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(CompanyPriceList::class, 'company_price_list_id');
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function serviceProblem(): BelongsTo
    {
        return $this->belongsTo(ServiceProblem::class);
    }

    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(Discount::class, 'discount_price_items')
            ->withTimestamps();
    }
}
