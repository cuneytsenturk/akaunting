<?php

namespace App\Http\Middleware;

use App\Events\Auth\Authenticated;
use Closure;
use Illuminate\Support\Facades\Auth;

class DynamicApiAuth
{
    /**
     * Handle an incoming request.
     *
     * This middleware dynamically switches between Basic Auth and OAuth (Passport)
     * based on the configuration. This allows seamless transition between auth
     * methods without breaking existing API implementations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Check if OAuth is enabled and which auth type to use
        $authType = config('oauth.auth_type', 'basic');
        $oauthEnabled = config('oauth.enabled', false);

        // If OAuth is not enabled, always use basic auth
        if (! $oauthEnabled) {
            return $this->authenticateWithBasic($request, $next);
        }

        // Switch based on auth type configuration
        switch ($authType) {
            case 'passport':
                return $this->authenticateWithPassport($request, $next);

            case 'basic':
            default:
                return $this->authenticateWithBasic($request, $next);
        }
    }

    /**
     * Authenticate using Basic Auth (onceBasic).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    protected function authenticateWithBasic($request, Closure $next)
    {
        $result = Auth::onceBasic();

        if ($result) {
            return $result;
        }

        // Fire authenticated event with basic protocol
        if ($user = Auth::user()) {
            $this->fireAuthenticatedEvent('api', $user, 'basic');
        }

        return $next($request);
    }

    /**
     * Authenticate using Laravel Passport (OAuth 2.0).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    protected function authenticateWithPassport($request, Closure $next)
    {
        // Use Passport guard for authentication
        $guard = config('oauth.guards.api', 'passport');

        // Check if user is authenticated via Passport
        if (! Auth::guard($guard)->check()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Fire authenticated event with passport protocol
        if ($user = Auth::guard($guard)->user()) {
            $this->fireAuthenticatedEvent('api', $user, 'passport');
        }

        return $next($request);
    }

    /**
     * Fire the authenticated event.
     *
     * @param  string  $alias
     * @param  \App\Models\Auth\User  $user
     * @param  string  $protocol
     * @return void
     */
    protected function fireAuthenticatedEvent($alias, $user, $protocol)
    {
        // Get company_id from request or user's first company
        $company_id = null;

        if (config('oauth.company_aware', true)) {
            $company_id = request()->header('X-Company-ID') 
                ?? request()->get('company_id')
                ?? company_id()
                ?? optional($user->companies()->first())->id;
        }

        event(new Authenticated($alias, $company_id, $protocol));
    }
}
