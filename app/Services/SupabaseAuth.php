<?php

namespace App\Services;

use App\Models\Admin;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SupabaseAuth
{
    public function authenticatedUser(string $accessToken): array
    {
        $response = $this->request($accessToken)->get('user');
        $this->ensureSuccessful($response, 'The authentication session is invalid or has expired.');

        return $response->json();
    }

    public function sendPasswordResetLink(string $email, string $redirectUrl): void
    {
        $response = $this->request()->post(
            'recover?redirect_to='.urlencode($redirectUrl),
            ['email' => strtolower(trim($email))],
        );

        $this->ensureSuccessful($response, 'Supabase could not send the password reset email.');
    }

    public function recoveryUser(string $accessToken): array
    {
        try {
            return $this->authenticatedUser($accessToken);
        } catch (RuntimeException) {
            throw new RuntimeException('The password reset link is invalid or has expired.');
        }
    }

    public function updatePassword(string $accessToken, string $password): void
    {
        $response = $this->request($accessToken)->put('user', [
            'password' => $password,
        ]);

        $this->ensureSuccessful($response, 'Supabase could not update the password.');
    }

    public function upsertAdminIdentity(string $email, string $password, string $name): string
    {
        $email = strtolower(trim($email));
        $identity = collect($this->adminUsers())
            ->first(fn (array $user) => strtolower((string) ($user['email'] ?? '')) === $email);

        $attributes = [
            'email' => $email,
            'password' => $password,
            'email_confirm' => true,
            'user_metadata' => [
                'account_type' => 'admin',
                'name' => $name,
            ],
        ];

        $response = $identity
            ? $this->request(useServiceRole: true)->put('admin/users/'.$identity['id'], $attributes)
            : $this->request(useServiceRole: true)->post('admin/users', $attributes);

        $this->ensureSuccessful($response, 'Supabase could not create the admin identity.');

        $authUserId = $response->json('id');
        if (! is_string($authUserId) || $authUserId === '') {
            throw new RuntimeException('Supabase did not return an admin identity ID.');
        }

        return $authUserId;
    }

    public function updateAdminEmail(string $authUserId, string $newEmail): void
    {
        $newEmail = strtolower(trim($newEmail));
        $response = $this->request(useServiceRole: true)->put('admin/users/'.$authUserId, [
            'email' => $newEmail,
            'email_confirm' => true,
        ]);

        $this->ensureSuccessful($response, 'Supabase could not update the admin email.');
    }

    public function updateAdminPasswordByAdminId(string $authUserId, string $password): void
    {
        $response = $this->request(useServiceRole: true)->put('admin/users/'.$authUserId, [
            'password' => $password,
        ]);

        $this->ensureSuccessful($response, 'Supabase could not update the admin password.');
    }

    public function syncAdminEmail(Admin $admin, string $newEmail): void
    {
        $newEmail = strtolower(trim($newEmail));

        if ($admin->auth_user_id) {
            $this->updateAdminEmail($admin->auth_user_id, $newEmail);
        } else {
            $existing = collect($this->adminUsers())
                ->first(fn (array $u) => strtolower((string) ($u['email'] ?? '')) === $newEmail);

            if ($existing) {
                $admin->auth_user_id = $existing['id'];
            } else {
                $authUserId = $this->upsertAdminIdentity($newEmail, Str::random(24), $admin->name);
                $admin->auth_user_id = $authUserId;
            }
        }
    }

    public function generateRecoveryLink(string $email, string $redirectUrl): ?string
    {
        $response = $this->request(useServiceRole: true)->post('admin/generate_link', [
            'type' => 'recovery',
            'email' => strtolower(trim($email)),
            'options' => [
                'redirect_to' => $redirectUrl,
            ],
        ]);

        $this->ensureSuccessful($response, 'Supabase could not generate a password recovery link.');

        return $response->json('action_link');
    }

    private function adminUsers(): array
    {
        $response = $this->request(useServiceRole: true)
            ->get('admin/users', ['page' => 1, 'per_page' => 1000]);

        $this->ensureSuccessful($response, 'Supabase could not load admin identities.');

        return $response->json('users', []);
    }

    private function request(?string $accessToken = null, bool $useServiceRole = false): PendingRequest
    {
        $url = rtrim((string) config('services.supabase.url'), '/');
        $apiKey = $useServiceRole
            ? config('services.supabase.service_role_key')
            : config('services.supabase.anon_key');

        if ($url === '' || ! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('Supabase Auth credentials are not configured.');
        }

        $request = Http::baseUrl($url.'/auth/v1')
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->withHeaders([
                'apikey' => $apiKey,
                'Authorization' => 'Bearer '.($accessToken ?: $apiKey),
            ]);

        $caBundle = $this->caBundlePath();

        return $caBundle
            ? $request->withOptions(['verify' => $caBundle])
            : $request;
    }

    private function ensureSuccessful(Response $response, string $fallbackMessage): void
    {
        if ($response->successful()) {
            return;
        }

        $message = $response->json('msg')
            ?? $response->json('message')
            ?? $response->json('error_description')
            ?? $fallbackMessage;

        throw new RuntimeException((string) $message);
    }

    private function caBundlePath(): ?string
    {
        $caBundle = config('services.supabase.ca_bundle');
        if (! is_string($caBundle) || $caBundle === '') {
            return null;
        }

        $path = preg_match('/^[A-Z]:\\\\/i', $caBundle) === 1 || str_starts_with($caBundle, '/')
            ? $caBundle
            : base_path($caBundle);

        return file_exists($path) ? $path : null;
    }
}
