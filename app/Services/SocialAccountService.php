<?php

namespace App\Services;

use App\Enums\SocialLoginProvider;
use App\Models\SocialAccount;
use App\Models\User;
use RuntimeException;

/**
 * Placeholder for future Google / Apple OAuth (customer app only).
 *
 * TODO: Integrate Laravel Socialite (or provider-specific SDKs) for Google and Apple.
 * TODO: Map provider user profile to users (type customer) and create/update social_accounts.
 * TODO: Store short-lived access tokens and refresh tokens in token_metadata (and consider encryption at rest).
 * TODO: Handle account linking: same email/phone on User vs provider email — decide merge rules.
 * TODO: Add logout/revocation and token refresh background job.
 */
class SocialAccountService
{
    /**
     * @param  array<string, mixed>  $tokenMetadata
     */
    public function linkAccount(User $user, SocialLoginProvider $provider, string $providerUserId, ?string $email = null, ?string $avatarUrl = null, ?array $tokenMetadata = null): SocialAccount
    {
        throw new RuntimeException('SocialAccountService::linkAccount is not implemented; use after OAuth is wired for customers.');
    }

    public function unlinkAccount(User $user, SocialLoginProvider $provider): void
    {
        throw new RuntimeException('SocialAccountService::unlinkAccount is not implemented; use after OAuth is wired for customers.');
    }

    public function findByProvider(SocialLoginProvider $provider, string $providerUserId): ?SocialAccount
    {
        return SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();
    }
}
