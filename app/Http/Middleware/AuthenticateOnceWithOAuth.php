<?php
 
namespace App\Http\Middleware;
 
use Illuminate\Support\Facades\Auth;
 
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

        // Check if user is authenticated via Passport
        if (! Auth::guard($guard)->check()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Fire authenticated event with passport protocol
        if ($user = Auth::guard($guard)->user()) {
            //$this->fireAuthenticatedEvent('api', $user, 'passport');
        }

        return $next($request);
    }
}