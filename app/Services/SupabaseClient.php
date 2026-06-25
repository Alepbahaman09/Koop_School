<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SupabaseClient
{
    public function rest(bool $useServiceRole = false): PendingRequest
    {
        $url = rtrim((string) config('services.supabase.url'), '/');
        $key = $useServiceRole
            ? config('services.supabase.service_role_key')
            : config('services.supabase.anon_key');

        if ($url === '' || ! $key) {
            throw new RuntimeException('Supabase URL and API key must be configured.');
        }

        $caBundle = $this->caBundlePath();

        return Http::baseUrl($url.'/rest/v1')
            ->acceptJson()
            ->when($caBundle !== null, fn (PendingRequest $request) => $request->withOptions([
                'verify' => $caBundle,
            ]))
            ->withHeaders([
                'apikey' => $key,
                'Authorization' => 'Bearer '.$key,
                'Accept-Profile' => config('services.supabase.schema', 'public'),
                'Content-Profile' => config('services.supabase.schema', 'public'),
            ]);
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
