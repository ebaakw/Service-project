<?php

namespace App\Services\Payments;

class MyFatoorahWebhookVerifier
{
    public function isValid(?string $signatureHeader, string $rawBody): bool
    {
        $secret = trim((string) config('myfatoorah.webhook_secret'));
        if ($secret === '') {
            // Allow unverified webhooks only in test mode when secret is empty
            // so local wiring can proceed; production should set the secret.
            return (bool) config('myfatoorah.is_test');
        }

        if ($signatureHeader === null || $signatureHeader === '') {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

        return hash_equals($expected, $signatureHeader);
    }
}
