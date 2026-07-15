<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use GuzzleHttp\Client;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FcmService
{
    private const MESSAGING_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function isConfigured(): bool
    {
        return $this->configurationError() === null;
    }

    public function configurationError(): ?string
    {
        if ($this->projectId() === '') {
            return 'FIREBASE_PROJECT_ID is missing.';
        }

        if (! is_file($this->credentialsPath())) {
            return 'The Firebase service-account file does not exist.';
        }

        try {
            $credentials = $this->credentials();
        } catch (\JsonException) {
            return 'The Firebase service-account file is not valid JSON.';
        }

        if (($credentials['type'] ?? null) !== 'service_account'
            || empty($credentials['client_email'])
            || empty($credentials['private_key'])) {
            return 'The Firebase credential must be a service-account JSON file, not google-services.json.';
        }

        if (($credentials['project_id'] ?? null) !== $this->projectId()) {
            return 'FIREBASE_PROJECT_ID does not match the service-account project.';
        }

        return null;
    }

    public function sendNotification(int $notificationId): void
    {
        $notification = DB::table('notifications')->find($notificationId);
        if (! $notification) {
            return;
        }

        $tokens = DB::table('fcm_devices')
            ->where('user_id', $notification->user_id)
            ->pluck('token');

        if ($tokens->isEmpty()) {
            return;
        }

        $accessToken = $this->accessToken();
        $data = json_decode($notification->data ?: '{}', true) ?: [];
        $receiptId = isset($data['receipt_id'])
            ? (string) $data['receipt_id']
            : null;

        foreach ($tokens as $token) {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout(15)
                ->withOptions(['verify' => $this->caBundlePath() ?? true])
                ->post($this->endpoint(), [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $notification->title,
                            'body' => $notification->body,
                        ],
                        'data' => array_filter([
                            'destination' => $receiptId ? 'receipt' : 'notifications',
                            'notificationId' => (string) $notification->id,
                            'receiptId' => $receiptId,
                            'productId' => isset($data['product_id'])
                                ? (string) $data['product_id']
                                : null,
                            'type' => (string) $notification->type,
                        ], fn ($value) => $value !== null),
                        'android' => [
                            'priority' => 'high',
                            'notification' => [
                                'channel_id' => 'koopik_notifications',
                            ],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                continue;
            }

            if ($this->tokenIsInvalid($response)) {
                DB::table('fcm_devices')->where('token', $token)->delete();

                continue;
            }

            throw new RuntimeException(
                'FCM rejected notification '.$notificationId.' with status '.$response->status().'.'
            );
        }
    }

    private function accessToken(): string
    {
        $serviceAccount = new ServiceAccountCredentials(
            self::MESSAGING_SCOPE,
            $this->credentials()
        );
        $httpHandler = HttpHandlerFactory::build(new Client([
            'verify' => $this->caBundlePath() ?? true,
        ]));
        $token = $serviceAccount->fetchAuthToken($httpHandler);

        if (empty($token['access_token'])) {
            throw new RuntimeException('Unable to obtain an FCM access token.');
        }

        return $token['access_token'];
    }

    private function tokenIsInvalid(Response $response): bool
    {
        $details = collect($response->json('error.details', []));

        return $details->contains(
            fn ($detail) => ($detail['errorCode'] ?? null) === 'UNREGISTERED'
        );
    }

    private function credentials(): array
    {
        return json_decode(
            file_get_contents($this->credentialsPath()),
            true,
            flags: JSON_THROW_ON_ERROR
        );
    }

    private function endpoint(): string
    {
        return 'https://fcm.googleapis.com/v1/projects/'
            .rawurlencode($this->projectId()).'/messages:send';
    }

    private function projectId(): string
    {
        return trim((string) config('services.firebase.project_id'));
    }

    private function credentialsPath(): string
    {
        $path = trim((string) config('services.firebase.credentials'));
        if ($path === '' || str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        if (preg_match('~^[A-Za-z]:[\\\\/]~', $path)) {
            return $path;
        }

        return base_path($path);
    }

    private function caBundlePath(): ?string
    {
        $path = trim((string) config('services.firebase.ca_bundle'));
        if ($path === '') {
            return null;
        }

        if (! str_starts_with($path, DIRECTORY_SEPARATOR)
            && ! preg_match('~^[A-Za-z]:[\\\\/]~', $path)) {
            $path = base_path($path);
        }

        return is_file($path) ? $path : null;
    }
}
