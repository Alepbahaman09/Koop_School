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
        $postgres = config('database.connections.pgsql');

        config([
            'database.default' => 'pgsql',
            'database.connections' => ['pgsql' => $postgres],
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
}
