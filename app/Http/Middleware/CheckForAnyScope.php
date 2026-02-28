<?php

namespace App\Http\Middleware;

use Closure;
use Laravel\Passport\Http\Middleware\CheckForAnyScope as Middleware;

class CheckForAnyScope extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, ...$scopes)
    {
        return parent::handle($request, $next, ...$scopes);
    }
}
