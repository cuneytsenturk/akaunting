<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthenticateOnceWithOAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        $guard = config('oauth.guards.api', 'passport');

        // Log incoming request for debugging
        Log::debug('OAuth: Attempting authentication', [
            'method' => $request->method(),
            'path' => $request->path(),
            'has_bearer' => $request->bearerToken() ? 'yes' : 'no',
            'bearer_preview' => $request->bearerToken() ? substr($request->bearerToken(), 0, 30) . '...' : null,
            'guard' => $guard,
        ]);

        // Check if user is authenticated via Passport
        if (! Auth::guard($guard)->check()) {
            Log::warning('OAuth: Authentication failed', [
                'guard' => $guard,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'message' => 'Unauthenticated.',
                'error' => 'invalid_token',
                'error_description' => 'The access token provided is expired, revoked, malformed, or invalid.',
            ], 401);
        }

        // Fire authenticated event with passport protocol
        if ($user = Auth::guard($guard)->user()) {
            Log::debug('OAuth: Authentication successful', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            // Set the user on the default guard so that the user() helper
            // and middleware like IdentifyCompany (which calls auth()->user())
            // can resolve the authenticated user without starting a session.
            Auth::setUser($user);
        }

        return $next($request);
    }
}
