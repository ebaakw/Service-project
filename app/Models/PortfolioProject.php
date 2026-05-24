<?php

namespace App\Models;

use App\Enums\PortfolioProjectReviewStatus;
use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PortfolioProject extends Model
{
    /** @use HasFactory<\Database\Factories\PortfolioProjectFactory> */
    use HasFactory, HasLocalizedFields, SoftDeletes;

    protected $fillable = [
        'company_id',
        'service_category_id',
        'technician_id',
        'title_ar',
        'title_en',
        'description_ar',
        'description_en',
        'location',
        'location_ar',
        'location_en',
        'completed_at',
        'is_featured',
        'is_active',
        'review_status',
        'reviewed_by_user_id',
        'reviewed_at',
        'rejection_reason',
    ];

    protected static function booted(): void
    {
        static::saving(function (PortfolioProject $project): void {
            $project->location = $project->location_ar ?: $project->location_en ?: $project->location;
        });
    }

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'review_status' => PortfolioProjectReviewStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(Technician::class, 'portfolio_project_technician')
            ->withTimestamps();
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function portfolioImages(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')
            ->where('collection_name', 'portfolio')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function allMedia(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }
}
