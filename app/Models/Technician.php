<?php

namespace App\Models;

use App\Enums\TechnicianAvailabilityStatus;
use App\Models\Concerns\HasLocalizedFields;
use Database\Factories\TechnicianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Technician extends Model
{
    /** @use HasFactory<TechnicianFactory> */
    use HasFactory, HasLocalizedFields, SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'employee_code',
        'display_name_ar',
        'display_name_en',
        'bio',
        'bio_ar',
        'bio_en',
        'job_title',
        'job_title_ar',
        'job_title_en',
        'availability_status',
        'current_lat',
        'current_lng',
        'last_location_at',
        'rating_average',
        'rating_count',
        'completed_orders_count',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saving(function (Technician $technician): void {
            $technician->bio = $technician->bio_ar ?: $technician->bio_en ?: $technician->bio;
            $technician->job_title = $technician->job_title_ar ?: $technician->job_title_en ?: $technician->job_title;
        });
    }

    /**
     * Display name with fallback to linked user name.
     */
    public function localizedDisplayName(?string $locale = null): ?string
    {
        $fromProfile = $this->localized('display_name', $locale);
        if ($fromProfile !== null) {
            return $fromProfile;
        }

        if ($this->relationLoaded('user') && $this->user !== null) {
            return $this->user->localized('name', $locale) ?? $this->user->name;
        }

        return null;
    }

    protected function casts(): array
    {
        return [
            'availability_status' => TechnicianAvailabilityStatus::class,
            'current_lat' => 'decimal:7',
            'current_lng' => 'decimal:7',
            'last_location_at' => 'datetime',
            'rating_average' => 'decimal:2',
            'rating_count' => 'integer',
            'completed_orders_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function serviceCategories(): BelongsToMany
    {
        return $this->belongsToMany(ServiceCategory::class, 'technician_service_categories')
            ->withTimestamps();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function orderOffers(): HasMany
    {
        return $this->hasMany(OrderOffer::class);
    }
}
