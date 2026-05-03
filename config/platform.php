<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform super admin (seeded via PlatformAdminSeeder)
    |--------------------------------------------------------------------------
    |
    | Values come from .env. Empty strings in .env do not fall back with env()
    | defaults alone; we use ?: so documented defaults apply when unset or blank.
    |
    */

    'admin' => [
        'name' => env('PLATFORM_ADMIN_NAME') ?: 'Super Admin',
        'email' => env('PLATFORM_ADMIN_EMAIL') ?: 'admin@example.com',
        'phone' => filled(env('PLATFORM_ADMIN_PHONE')) ? trim((string) env('PLATFORM_ADMIN_PHONE')) : null,
        'password' => env('PLATFORM_ADMIN_PASSWORD') ?: 'password',
    ],

];
