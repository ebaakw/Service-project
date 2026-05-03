<?php

namespace Database\Factories;

use App\Enums\SocialLoginProvider;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => SocialLoginProvider::Google,
            'provider_user_id' => (string) Str::ulid(),
            'email' => fake()->optional()->safeEmail(),
            'avatar_url' => null,
            'token_metadata' => null,
        ];
    }
}
