<?php

namespace App\Services\Payments;

class MyFatoorahWebhookVerifier
{
    /**
     * Verify MyFatoorah-Signature for webhook v2 (ordered fields) with v1 raw-body fallback.
     *
     * @param  array<string, mixed>|null  $payload
     */
    public function isValid(?string $signatureHeader, string $rawBody, ?array $payload = null): bool
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

        $payload ??= $this->decodePayload($rawBody);

        if (is_array($payload) && $this->looksLikeV2Payload($payload)) {
            $expectedV2 = $this->signatureForV2($payload, $secret);
            if ($expectedV2 !== null && hash_equals($expectedV2, $signatureHeader)) {
                return true;
            }
        }

        // Fallback: webhook signing version v1 (HMAC over raw body).
        $expectedV1 = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

        return hash_equals($expectedV1, $signatureHeader);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function signatureForV2(array $payload, string $secret): ?string
    {
        $data = is_array($payload['Data'] ?? null) ? $payload['Data'] : $payload;
        if (! is_array($data)) {
            return null;
        }

        $invoice = is_array($data['Invoice'] ?? null) ? $data['Invoice'] : [];
        $transaction = is_array($data['Transaction'] ?? null) ? $data['Transaction'] : [];

        $parts = [
            'Invoice.Id='.$this->stringOrEmpty($invoice['Id'] ?? null),
            'Invoice.Status='.$this->stringOrEmpty($invoice['Status'] ?? null),
            'Transaction.Status='.$this->stringOrEmpty($transaction['Status'] ?? null),
            'Transaction.PaymentId='.$this->stringOrEmpty($transaction['PaymentId'] ?? null),
            'Invoice.ExternalIdentifier='.$this->stringOrEmpty($invoice['ExternalIdentifier'] ?? null),
        ];

        $ordered = implode(',', $parts);

        return base64_encode(hash_hmac('sha256', $ordered, $secret, true));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function looksLikeV2Payload(array $payload): bool
    {
        $data = is_array($payload['Data'] ?? null) ? $payload['Data'] : $payload;

        return is_array($data)
            && (isset($data['Invoice']) || isset($data['Transaction']));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodePayload(string $rawBody): ?array
    {
        if ($rawBody === '') {
            return null;
        }

        $decoded = json_decode($rawBody, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function stringOrEmpty(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return (string) $value;
    }
}
