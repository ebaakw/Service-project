<?php

namespace Database\Seeders;

use App\Enums\RoleScope;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class PlatformAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure newly added permission keys exist before syncing the super role.
        $this->call(PermissionSeeder::class);

        $admin = config('platform.admin', []);

        $name = isset($admin['name']) && is_string($admin['name']) ? trim($admin['name']) : '';
        $email = isset($admin['email']) && is_string($admin['email']) ? trim($admin['email']) : '';
        $password = isset($admin['password']) && is_string($admin['password']) ? $admin['password'] : '';
        $phone = isset($admin['phone']) && is_string($admin['phone']) && $admin['phone'] !== ''
            ? trim($admin['phone'])
            : null;

        if ($email === '' || $password === '') {
            throw new InvalidArgumentException(
                'Platform admin seeding requires non-empty email and password. Set PLATFORM_ADMIN_EMAIL and PLATFORM_ADMIN_PASSWORD in .env (see .env.example).'
            );
        }

        if ($name === '') {
            $name = 'Super Admin';
        }

        $user = User::query()->where('email', $email)->first();

        $attributes = [
            'name' => $name,
            'type' => UserType::PlatformAdmin,
            'status' => UserStatus::Active,
        ];

        if (Schema::hasColumn('users', 'name_ar')) {
            $attributes['name_ar'] = $name;
        }
        if (Schema::hasColumn('users', 'name_en')) {
            $attributes['name_en'] = $name;
        }

        if ($user === null) {
            $attributes['email'] = $email;
            $attributes['password'] = $password;
            $attributes['email_verified_at'] = now();
            $attributes['phone'] = $phone;
            $attributes['phone_verified_at'] = $phone !== null ? now() : null;

            $user = User::query()->create($attributes);
        } else {
            $user->fill($attributes);
            $user->phone = $phone;

            if ($phone === null) {
                $user->phone_verified_at = null;
            } elseif ($user->phone_verified_at === null) {
                $user->phone_verified_at = now();
            }

            if ($user->email_verified_at === null) {
                $user->email_verified_at = now();
            }

            if (! Hash::check($password, $user->getAuthPassword())) {
                $user->password = $password;
            }

            $user->save();
        }

        $role = Role::query()->updateOrCreate(
            [
                'company_id' => null,
                'name' => 'platform_super',
                'scope' => RoleScope::Platform->value,
            ],
            [
                'display_name' => 'Platform super admin',
                'is_system' => true,
            ]
        );

        $permissionIds = Permission::query()->pluck('id')->all();
        $role->permissions()->sync($permissionIds);

        $user->roles()->sync([
            $role->id => ['company_id' => null],
        ]);
    }
}
