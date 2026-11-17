<?php

namespace App\Services\Push;

use App\Models\UserDevice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class PushNotificationService
{
    /**
     * @param  Collection<int, UserDevice>|UserDevice  $devices
     * @param  array<string, mixed>  $data
     */
    public function send(Collection|UserDevice $devices, string $title, string $body, array $data = []): void
    {
        $list = $devices instanceof UserDevice ? collect([$devices]) : $devices;
        if ($list->isEmpty()) {
            return;
        }

        $stringData = [];
        foreach ($data as $key => $value) {
            $stringData[(string) $key] = is_scalar($value) || $value === null
                ? (string) $value
                : json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $credentials = $this->loadCredentials();
        if ($credentials !== null) {
            $this->sendViaHttpV1($list, $credentials, $title, $body, $stringData);

            return;
        }

        $serverKey = (string) config('services.fcm.server_key', '');
        if ($serverKey !== '') {
            $this->sendViaLegacy($list, $serverKey, $title, $body, $stringData);

            return;
        }

        Log::info('FCM skipped: no FCM credentials configured', [
            'device_count' => $list->count(),
            'title' => $title,
        ]);
    }

    /**
     * @param  Collection<int, UserDevice>  $devices
     * @param  array{project_id: string, client_email: string, private_key: string}  $credentials
     * @param  array<string, string>  $stringData
     */
    private function sendViaHttpV1(
        Collection $devices,
        array $credentials,
        string $title,
        string $body,
        array $stringData,
    ): void {
        try {
            $accessToken = $this->accessToken($credentials);
        } catch (Throwable $e) {
            Log::error('FCM V1 access token failed', ['error' => $e->getMessage()]);

            return;
        }

        $endpoint = sprintf(
            'https://fcm.googleapis.com/v1/projects/%s/messages:send',
            rawurlencode($credentials['project_id']),
        );

        foreach ($devices as $device) {
            if (! $device instanceof UserDevice || blank($device->fcm_token)) {
                continue;
            }

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post($endpoint, [
                    'message' => [
                        'token' => $device->fcm_token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => $stringData,
                        'android' => [
                            'priority' => 'high',
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('FCM V1 send failed', [
                    'device_id' => $device->getKey(),
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, UserDevice>  $devices
     * @param  array<string, string>  $stringData
     */
    private function sendViaLegacy(
        Collection $devices,
        string $serverKey,
        string $title,
        string $body,
        array $stringData,
    ): void {
        foreach ($devices as $device) {
            if (! $device instanceof UserDevice || blank($device->fcm_token)) {
                continue;
            }

            $response = Http::withHeaders([
                'Authorization' => 'key='.$serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $device->fcm_token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $stringData,
                'priority' => 'high',
            ]);

            if (! $response->successful()) {
                Log::warning('FCM legacy send failed', [
                    'device_id' => $device->getKey(),
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        }
    }

    /**
     * @return array{project_id: string, client_email: string, private_key: string}|null
     */
    private function loadCredentials(): ?array
    {
        $path = (string) config('services.fcm.credentials', '');
        if ($path === '') {
            return null;
        }

        $resolved = $this->resolvePath($path);
        if ($resolved === null || ! is_readable($resolved)) {
            Log::warning('FCM credentials file missing or unreadable', ['path' => $path]);

            return null;
        }

        /** @var array<string, mixed>|null $json */
        $json = json_decode((string) file_get_contents($resolved), true);
        if (! is_array($json)) {
            Log::warning('FCM credentials JSON is invalid', ['path' => $resolved]);

            return null;
        }

        $projectId = (string) ($json['project_id'] ?? '');
        $clientEmail = (string) ($json['client_email'] ?? '');
        $privateKey = (string) ($json['private_key'] ?? '');

        if ($projectId === '' || $clientEmail === '' || $privateKey === '') {
            Log::warning('FCM credentials JSON missing required fields', ['path' => $resolved]);

            return null;
        }

        return [
            'project_id' => $projectId,
            'client_email' => $clientEmail,
            'private_key' => $privateKey,
        ];
    }

    private function resolvePath(string $path): ?string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }

    /**
     * @param  array{project_id: string, client_email: string, private_key: string}  $credentials
     */
    private function accessToken(array $credentials): string
    {
        $cacheKey = 'fcm.oauth.access_token.'.sha1($credentials['client_email']);

        /** @var string|null $cached */
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $now = time();
        $jwtHeader = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $jwtClaim = $this->base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));

        $unsigned = $jwtHeader.'.'.$jwtClaim;
        $privateKey = openssl_pkey_get_private($credentials['private_key']);
        if ($privateKey === false) {
            throw new RuntimeException('Invalid FCM private key.');
        }

        $signature = '';
        $ok = openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (! $ok) {
            throw new RuntimeException('Failed to sign FCM JWT.');
        }

        $assertion = $unsigned.'.'.$this->base64UrlEncode($signature);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('OAuth token request failed: '.$response->body());
        }

        $accessToken = (string) $response->json('access_token');
        $expiresIn = (int) ($response->json('expires_in') ?? 3600);
        if ($accessToken === '') {
            throw new RuntimeException('OAuth token response missing access_token.');
        }

        Cache::put($cacheKey, $accessToken, max(60, $expiresIn - 60));

        return $accessToken;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
