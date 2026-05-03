<?php

namespace App\Models;

use App\Enums\DiscountScope;
use App\Enums\DiscountType;
use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    /** @use HasFactory<\Database\Factories\DiscountFactory> */
    use HasFactory, HasLocalizedFields, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'name_ar',
        'name_en',
        'description',
        'description_ar',
        'description_en',
        'discount_type',
        'discount_value',
        'discount_scope',
        'starts_at',
        'ends_at',
        'max_discount_amount',
        'min_order_amount',
        'usage_limit',
        'used_count',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saving(function (Discount $discount): void {
            $discount->name = $discount->name_ar ?: $discount->name_en ?: $discount->name;
            $discount->description = $discount->description_ar ?: $discount->description_en ?: $discount->description;
        });
    }

    protected function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,
            'discount_scope' => DiscountScope::class,
            'discount_value' => 'decimal:3',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'max_discount_amount' => 'decimal:3',
            'min_order_amount' => 'decimal:3',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ServiceCategory::class, 'discount_categories')
            ->withTimestamps();
    }

    public function priceItems(): BelongsToMany
    {
        return $this->belongsToMany(CompanyPriceItem::class, 'discount_price_items')
            ->withTimestamps();
    }
}
