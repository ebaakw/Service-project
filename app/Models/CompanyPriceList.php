<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyPriceList extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyPriceListFactory> */
    use HasFactory, HasLocalizedFields, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'name_ar',
        'name_en',
        'description',
        'description_ar',
        'description_en',
        'is_default',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saving(function (CompanyPriceList $list): void {
            $list->name = $list->name_ar ?: $list->name_en ?: $list->name;
            $list->description = $list->description_ar ?: $list->description_en ?: $list->description;
        });
    }

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CompanyPriceItem::class)->orderBy('sort_order');
    }
}
