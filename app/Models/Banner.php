<?php

namespace App\Models;

use App\Enums\BannerLinkType;
use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Banner extends Model
{
    /** @use HasFactory<\Database\Factories\BannerFactory> */
    use HasFactory, HasLocalizedFields, SoftDeletes;

    protected $fillable = [
        'ulid',
        'title_ar',
        'title_en',
        'subtitle_ar',
        'subtitle_en',
        'cta_label_ar',
        'cta_label_en',
        'image_path',
        'link_type',
        'link_value',
        'sort_order',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Banner $banner): void {
            if (empty($banner->ulid)) {
                $banner->ulid = (string) Str::ulid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'link_type' => BannerLinkType::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    public function scopeCurrentlyVisible(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where(function (Builder $q) use ($now): void {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now): void {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function imageUrl(): ?string
    {
        if ($this->image_path === null || $this->image_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->image_path);
    }
}
