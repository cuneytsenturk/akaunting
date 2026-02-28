<?php

namespace App\Http\Middleware;

use Closure;
use Laravel\Passport\Http\Middleware\CheckScopes as Middleware;

class CheckScopes extends Middleware
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$scopes
     * @return \Illuminate\Http\Response
     *
     * @throws \Laravel\Passport\Exceptions\AuthenticationException|\Laravel\Passport\Exceptions\MissingScopeException
     */
    public function handle($request, Closure $next, ...$scopes)
    {
        return parent::handle($request, $next, ...$scopes);
    }
}
