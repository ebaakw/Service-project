<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MyFatoorahClient
{
    public function baseUrl(): string
    {
        $configured = trim((string) config('myfatoorah.api_base_url'));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        if ((bool) config('myfatoorah.is_test')) {
            return 'https://apitest.myfatoorah.com';
        }

        return match (strtoupper((string) config('myfatoorah.country_iso', 'KWT'))) {
            'SAU' => 'https://api-sa.myfatoorah.com',
            'ARE' => 'https://api-ae.myfatoorah.com',
            'QAT' => 'https://api-qa.myfatoorah.com',
            'BHR' => 'https://api-bh.myfatoorah.com',
            'OMN' => 'https://api-om.myfatoorah.com',
            'JOR', 'JOD' => 'https://api-jo.myfatoorah.com',
            'EGY' => 'https://api-eg.myfatoorah.com',
            default => 'https://api-kw.myfatoorah.com',
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sendPayment(array $payload): array
    {
        return $this->post('/v2/SendPayment', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPaymentStatus(string $key, string $keyType = 'InvoiceId'): array
    {
        return $this->post('/v2/GetPaymentStatus', [
            'Key' => $key,
            'KeyType' => $keyType,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload): array
    {
        $apiKey = trim((string) config('myfatoorah.api_key'));
        if ($apiKey === '') {
            throw new RuntimeException('MyFatoorah API key is not configured.');
        }

        try {
            $response = Http::baseUrl($this->baseUrl())
                ->withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post($path, $payload)
                ->throw();
        } catch (RequestException $e) {
            $body = $e->response?->json();
            $message = is_array($body)
                ? (string) ($body['Message'] ?? $body['message'] ?? $e->getMessage())
                : $e->getMessage();

            throw new RuntimeException('MyFatoorah request failed: '.$message, previous: $e);
        }

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];

        if (($json['IsSuccess'] ?? false) !== true) {
            throw new RuntimeException(
                'MyFatoorah error: '.(string) ($json['Message'] ?? 'Unknown error')
            );
        }

        $data = $json['Data'] ?? null;
        if (! is_array($data)) {
            throw new RuntimeException('MyFatoorah response missing Data.');
        }

        return $data;
    }
}
