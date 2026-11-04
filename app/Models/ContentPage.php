<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ContentPage extends Model
{
    /** @use HasFactory<\Database\Factories\ContentPageFactory> */
    use HasFactory, HasLocalizedFields, SoftDeletes;

    protected $fillable = [
        'ulid',
        'slug',
        'title_ar',
        'title_en',
        'body_ar',
        'body_en',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function (ContentPage $page): void {
            if (empty($page->ulid)) {
                $page->ulid = (string) Str::ulid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
