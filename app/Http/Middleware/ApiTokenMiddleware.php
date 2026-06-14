<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = $request->bearerToken();
        $token = $plainTextToken
            ? ApiToken::with('user')->where('token_hash', hash('sha256', $plainTextToken))->first()
            : null;

        if (! $token || ($token->expires_at && $token->expires_at->isPast())) {
            $token?->delete();

            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if (! $token->last_used_at || $token->last_used_at->lt(now()->subMinutes(5))) {
            $token->forceFill(['last_used_at' => now()])->save();
        }

        $request->attributes->set('api_token', $token);
        $request->setUserResolver(fn () => $token->user);

        return $next($request);
    }
}
