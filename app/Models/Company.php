<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Concerns\HasLocalizedFields;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, HasLocalizedFields, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Company $company): void {
            if (empty($company->ulid)) {
                $company->ulid = (string) Str::ulid();
            }
        });

        static::saving(function (Company $company): void {
            // Keep legacy single-language columns in sync for slug/search/back-compat.
            $company->name = $company->name_ar ?: $company->name_en ?: $company->name;
            $company->short_description = $company->short_description_ar ?: $company->short_description_en ?: $company->short_description;
            $company->description = $company->description_ar ?: $company->description_en ?: $company->description;
        });
    }

    protected $fillable = [
        'ulid',
        'name',
        'name_ar',
        'name_en',
        'slug',
        'logo_path',
        'short_description',
        'short_description_ar',
        'short_description_en',
        'description',
        'description_ar',
        'description_en',
        'warranty_note_ar',
        'warranty_note_en',
        'phone',
        'email',
        'contacts',
        'address',
        'working_hours',
        'iban',
        'bank_name',
        'status',
        'subscription_status',
        'subscription_starts_at',
        'subscription_ends_at',
        'rating_average',
        'rating_count',
        'is_featured',
        'default_inspection_fee',
        'allow_final_invoice_without_items',
    ];

    public const DOCUMENT_COLLECTIONS = [
        'license',
        'signature_authorization',
        'additional_documents',
    ];

    protected function casts(): array
    {
        return [
            'contacts' => 'array',
            'status' => CompanyStatus::class,
            'subscription_status' => SubscriptionStatus::class,
            'subscription_starts_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'rating_average' => 'decimal:2',
            'rating_count' => 'integer',
            'is_featured' => 'boolean',
            'default_inspection_fee' => 'decimal:3',
            'allow_final_invoice_without_items' => 'boolean',
        ];
    }

    public function getDisplayNameAttribute(): ?string
    {
        return $this->localized('name');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CompanyMember::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function priceLists(): HasMany
    {
        return $this->hasMany(CompanyPriceList::class);
    }

    public function priceItems(): HasMany
    {
        return $this->hasMany(CompanyPriceItem::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class);
    }

    public function technicians(): HasMany
    {
        return $this->hasMany(Technician::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function companySettings(): HasMany
    {
        return $this->hasMany(CompanySetting::class);
    }

    public function portfolioProjects(): HasMany
    {
        return $this->hasMany(PortfolioProject::class);
    }

    public function feedbackTickets(): HasMany
    {
        return $this->hasMany(FeedbackTicket::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CompanyReview::class);
    }

    public function favoritedByCustomers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_favorite_companies')
            ->withTimestamps();
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }
}
