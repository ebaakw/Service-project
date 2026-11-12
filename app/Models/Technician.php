<?php

namespace App\Models;

use App\Enums\TechnicianAvailabilityStatus;
use App\Enums\TechnicianReviewStatus;
use App\Models\Concerns\HasLocalizedFields;
use Database\Factories\TechnicianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Technician extends Model
{
    /** @use HasFactory<TechnicianFactory> */
    use HasFactory, HasLocalizedFields, SoftDeletes;

    public const CIVIL_ID_COLLECTION = 'civil_id';

    protected $fillable = [
        'company_id',
        'user_id',
        'first_name_ar',
        'second_name_ar',
        'family_name_ar',
        'first_name_en',
        'second_name_en',
        'family_name_en',
        'civil_id_number',
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
        'is_accepting_orders',
        'estimated_job_duration_minutes',
        'current_lat',
        'current_lng',
        'last_location_at',
        'rating_average',
        'rating_count',
        'completed_orders_count',
        'is_active',
        'review_status',
        'reviewed_by_user_id',
        'reviewed_at',
        'rejection_reason',
        'pending_changes',
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

    public static function composeFullName(?string $first, ?string $second, ?string $family): ?string
    {
        $parts = array_values(array_filter([
            trim((string) $first),
            trim((string) $second),
            trim((string) $family),
        ], static fn (string $part): bool => $part !== ''));

        return $parts === [] ? null : implode(' ', $parts);
    }

    public function composedNameAr(): ?string
    {
        return self::composeFullName($this->first_name_ar, $this->second_name_ar, $this->family_name_ar);
    }

    public function composedNameEn(): ?string
    {
        return self::composeFullName($this->first_name_en, $this->second_name_en, $this->family_name_en);
    }

    public function civilIdMedia(): ?Media
    {
        if ($this->relationLoaded('media')) {
            return $this->media
                ->where('collection_name', self::CIVIL_ID_COLLECTION)
                ->sortByDesc('id')
                ->first();
        }

        return $this->media()
            ->where('collection_name', self::CIVIL_ID_COLLECTION)
            ->orderByDesc('id')
            ->first();
    }

    protected function casts(): array
    {
        return [
            'availability_status' => TechnicianAvailabilityStatus::class,
            'is_accepting_orders' => 'boolean',
            'estimated_job_duration_minutes' => 'integer',
            'current_lat' => 'decimal:7',
            'current_lng' => 'decimal:7',
            'last_location_at' => 'datetime',
            'rating_average' => 'decimal:2',
            'rating_count' => 'integer',
            'completed_orders_count' => 'integer',
            'is_active' => 'boolean',
            'review_status' => TechnicianReviewStatus::class,
            'reviewed_at' => 'datetime',
            'pending_changes' => 'array',
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

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    public function serviceCategories(): BelongsToMany
    {
        return $this->belongsToMany(ServiceCategory::class, 'technician_service_categories')
            ->withTimestamps();
    }

    public function portfolioProjects(): BelongsToMany
    {
        return $this->belongsToMany(PortfolioProject::class, 'portfolio_project_technician')
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

    public function favoritedByCustomers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_favorite_technicians')
            ->withTimestamps();
    }
}
