<?php

namespace App\Models;

use App\Enums\DiscountType;
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
        'discount_type',
        'discount_value',
        'inspection_fee',
        'warranty_days',
        'warranty_description_ar',
        'warranty_description_en',
        'duration_minutes',
        'sort_order',
        'is_active',
    ];

    protected $appends = [
        'effective_discount_type',
        'effective_discount_value',
        'discounted_price',
    ];

    protected function casts(): array
    {
        return [
            'price_type' => PriceType::class,
            'price' => 'decimal:3',
            'minimum_price' => 'decimal:3',
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:3',
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

    public function getEffectiveDiscountTypeAttribute(): ?string
    {
        if ($this->discount_type !== null && $this->discount_value !== null) {
            return $this->discount_type instanceof DiscountType ? $this->discount_type->value : (string) $this->discount_type;
        }

        $priceList = $this->relationLoaded('priceList') ? $this->priceList : $this->priceList()->first();

        if ($priceList?->discount_type === null || $priceList->discount_value === null) {
            return null;
        }

        return $priceList->discount_type instanceof DiscountType
            ? $priceList->discount_type->value
            : (string) $priceList->discount_type;
    }

    public function getEffectiveDiscountValueAttribute(): ?string
    {
        if ($this->discount_type !== null && $this->discount_value !== null) {
            return (string) $this->discount_value;
        }

        $priceList = $this->relationLoaded('priceList') ? $this->priceList : $this->priceList()->first();

        return $priceList?->discount_value !== null ? (string) $priceList->discount_value : null;
    }

    public function getDiscountedPriceAttribute(): ?string
    {
        if ($this->price === null) {
            return null;
        }

        $type = $this->effective_discount_type;
        $value = $this->effective_discount_value;

        if ($type === null || $value === null) {
            return (string) $this->price;
        }

        $price = (float) $this->price;
        $discountValue = (float) $value;

        $discounted = $type === DiscountType::Percentage->value
            ? $price * (1 - ($discountValue / 100))
            : max(0, $price - $discountValue);

        return number_format(round($discounted, 3), 3, '.', '');
    }
}
