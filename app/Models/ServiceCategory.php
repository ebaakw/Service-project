<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServiceCategory extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceCategoryFactory> */
    use HasFactory, HasLocalizedFields, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (ServiceCategory $category): void {
            if (empty($category->ulid)) {
                $category->ulid = (string) Str::ulid();
            }
        });
    }

    protected $fillable = [
        'ulid',
        'name_ar',
        'name_en',
        'slug',
        'icon_path',
        'description_ar',
        'description_en',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    public function iconUrl(): ?string
    {
        if ($this->icon_path === null || $this->icon_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->icon_path);
    }

    public function problems(): HasMany
    {
        return $this->hasMany(ServiceProblem::class)->orderBy('sort_order');
    }

    public function intakeFields(): HasMany
    {
        return $this->hasMany(ServiceIntakeField::class)->orderBy('sort_order');
    }

    public function priceItems(): HasMany
    {
        return $this->hasMany(CompanyPriceItem::class);
    }

    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(Discount::class, 'discount_categories')
            ->withTimestamps();
    }

    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(Technician::class, 'technician_service_categories')
            ->withTimestamps();
    }
}
