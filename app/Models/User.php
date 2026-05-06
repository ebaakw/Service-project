<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Concerns\HasLocalizedFields;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'name_ar',
        'name_en',
        'email',
        'phone',
        'password',
        'type',
        'status',
        'avatar_path',
        'ulid',
        'email_verified_at',
        'phone_verified_at',
        'last_login_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasLocalizedFields, Notifiable, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (empty($user->ulid)) {
                $user->ulid = (string) Str::ulid();
            }
        });

        static::saving(function (User $user): void {
            $user->name = $user->name_ar ?: $user->name_en ?: $user->name;
        });
    }

    protected function casts(): array
    {
        return [
            'type' => UserType::class,
            'status' => UserStatus::class,
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function companyMembers(): HasMany
    {
        return $this->hasMany(CompanyMember::class);
    }

    public function technician(): HasOne
    {
        return $this->hasOne(Technician::class);
    }

    public function customerProfile(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('company_id')
            ->withTimestamps(false, false);
    }

    public function isPlatformAdmin(): bool
    {
        return $this->type === UserType::PlatformAdmin;
    }

    public function isCompanyUser(): bool
    {
        return $this->type === UserType::CompanyUser;
    }

    public function isTechnician(): bool
    {
        return $this->type === UserType::Technician;
    }

    public function isCustomer(): bool
    {
        return $this->type === UserType::Customer;
    }

    /**
     * Permission keys for platform scope (company_id null on pivot) and current company (when provided).
     *
     * @return list<string>
     */
    public function permissionKeys(?int $companyId = null): array
    {
        $query = $this->roles()->with('permissions');

        $query->where(function ($q) use ($companyId): void {
            $q->whereNull('user_roles.company_id');
            if ($companyId !== null) {
                $q->orWhere('user_roles.company_id', $companyId);
            }
        });

        return $query->get()
            ->flatMap(fn (Role $role) => $role->permissions->pluck('key'))
            ->unique()
            ->values()
            ->all();
    }

    public function hasPermission(string $key, ?int $companyId = null): bool
    {
        return in_array($key, $this->permissionKeys($companyId), true);
    }

    /**
     * @param  list<string>  $keys
     */
    public function hasAnyPermission(array $keys, ?int $companyId = null): bool
    {
        $granted = $this->permissionKeys($companyId);

        foreach ($keys as $key) {
            if (in_array($key, $granted, true)) {
                return true;
            }
        }

        return false;
    }

    public function assignedFeedbackTickets(): HasMany
    {
        return $this->hasMany(FeedbackTicket::class, 'assigned_to_user_id');
    }

    public function feedbackReplies(): HasMany
    {
        return $this->hasMany(FeedbackReply::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }
}
