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
        $secret = $this->normalizedSecret();
        if ($secret === '') {
            // Allow unverified webhooks only in test mode when secret is empty
            // so local wiring can proceed; production should set the secret.
            return (bool) config('myfatoorah.is_test');
        }

        $signature = $this->normalizedSignature($signatureHeader);
        if ($signature === '') {
            return false;
        }

        $payload ??= $this->decodePayload($rawBody);

        if (is_array($payload) && $this->looksLikeV2Payload($payload)) {
            $expectedV2 = $this->signatureForV2($payload, $secret);
            if ($expectedV2 !== null && hash_equals($expectedV2, $signature)) {
                return true;
            }
        }

        // Fallback: webhook signing version v1 (HMAC over raw body).
        $expectedV1 = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

        return hash_equals($expectedV1, $signature);
    }

    /**
     * Safe diagnostics for failed verification (never includes the secret).
     *
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>
     */
    public function failureContext(?string $signatureHeader, string $rawBody, ?array $payload = null): array
    {
        $secret = $this->normalizedSecret();
        $signature = $this->normalizedSignature($signatureHeader);
        $payload ??= $this->decodePayload($rawBody);
        $ordered = is_array($payload) ? $this->orderedStringForV2($payload) : null;
        $expectedV2 = ($ordered !== null && $secret !== '')
            ? base64_encode(hash_hmac('sha256', $ordered, $secret, true))
            : null;

        return [
            'secret_configured' => $secret !== '',
            'secret_length' => strlen($secret),
            'signature_length' => strlen($signature),
            'body_bytes' => strlen($rawBody),
            'event' => is_array($payload) ? data_get($payload, 'Event.Name') : null,
            'ordered_v2' => $ordered,
            'expected_v2_length' => is_string($expectedV2) ? strlen($expectedV2) : null,
            'expected_matches_header' => is_string($expectedV2) && $signature !== ''
                ? hash_equals($expectedV2, $signature)
                : false,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function signatureForV2(array $payload, string $secret): ?string
    {
        $ordered = $this->orderedStringForV2($payload);
        if ($ordered === null) {
            return null;
        }

        return base64_encode(hash_hmac('sha256', $ordered, $secret, true));
    }

    /**
     * Canonical PAYMENT_STATUS_CHANGED string per MyFatoorah webhook v2 docs.
     *
     * @param  array<string, mixed>  $payload
     */
    public function orderedStringForV2(array $payload): ?string
    {
        $data = is_array($payload['Data'] ?? null) ? $payload['Data'] : $payload;
        if (! is_array($data)) {
            return null;
        }

        $invoice = is_array($data['Invoice'] ?? null) ? $data['Invoice'] : [];
        $transaction = is_array($data['Transaction'] ?? null) ? $data['Transaction'] : [];

        return implode(',', [
            'Invoice.Id='.$this->stringOrEmpty($invoice['Id'] ?? null),
            'Invoice.Status='.$this->stringOrEmpty($invoice['Status'] ?? null),
            'Transaction.Status='.$this->stringOrEmpty($transaction['Status'] ?? null),
            'Transaction.PaymentId='.$this->stringOrEmpty($transaction['PaymentId'] ?? null),
            'Invoice.ExternalIdentifier='.$this->stringOrEmpty($invoice['ExternalIdentifier'] ?? null),
        ]);
    }

    private function normalizedSecret(): string
    {
        $secret = trim((string) config('myfatoorah.webhook_secret'));
        // Common .env paste artifacts: wrapping quotes / accidental whitespace.
        if (
            strlen($secret) >= 2
            && (
                ($secret[0] === '"' && str_ends_with($secret, '"'))
                || ($secret[0] === "'" && str_ends_with($secret, "'"))
            )
        ) {
            $secret = substr($secret, 1, -1);
        }

        return trim($secret);
    }

    private function normalizedSignature(?string $signatureHeader): string
    {
        return trim((string) $signatureHeader);
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
