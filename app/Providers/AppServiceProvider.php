<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->ensureSupabaseDatabase();

        RateLimiter::for('api-auth', function (Request $request) {
            return Limit::perMinute(10)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('api-me', function (Request $request) {
            return Limit::perMinute(12)->by($this->apiClientKey($request));
        });

        RateLimiter::for('api-documents', function (Request $request) {
            return Limit::perMinute(600)->by($this->apiClientKey($request));
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($this->apiClientKey($request));
        });
    }

    private function apiClientKey(Request $request): string
    {
        $token = $request->bearerToken();

        return $token ? hash('sha256', $token) : $request->ip();
    }

    private function ensureSupabaseDatabase(): void
    {
        $url = (string) config('database.connections.pgsql.url', '');
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $localHosts = ['localhost', '127.0.0.1', '0.0.0.0', '10.0.2.2'];

        $isSupabaseHost = str_ends_with($host, '.supabase.com') || str_ends_with($host, '.supabase.co');

        if ($host === '' || ! $isSupabaseHost || in_array($host, $localHosts, true) || str_starts_with($host, '192.168.') || str_starts_with($host, '10.')) {
            throw new \RuntimeException('DATABASE_URL must point to Supabase Postgres, not a local or private database.');
        }

        if (config('database.connections.pgsql.sslmode') !== 'require') {
            throw new \RuntimeException('DB_SSLMODE=require is required for Supabase Postgres.');
        }
    }
}
