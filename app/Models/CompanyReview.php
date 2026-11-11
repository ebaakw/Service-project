<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Database\Factories\CompanyReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CompanyReview extends Model
{
    /** @use HasFactory<CompanyReviewFactory> */
    use HasFactory, HasLocalizedFields;

    protected $fillable = [
        'company_id',
        'customer_id',
        'rating',
        'author_name',
        'comment',
        'comment_ar',
        'comment_en',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CompanyReview $review): void {
            $review->comment = $review->comment_ar ?: $review->comment_en ?: $review->comment;
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')->orderBy('sort_order');
    }

    public function reviewImages(): MorphMany
    {
        return $this->media()->where('collection_name', 'review');
    }

    public function resolveAuthorName(): ?string
    {
        $explicit = trim((string) $this->author_name);
        if ($explicit !== '') {
            return $explicit;
        }

        $this->loadMissing('customer.user');

        return $this->customer?->user?->localized('name')
            ?: $this->customer?->user?->name;
    }
}
