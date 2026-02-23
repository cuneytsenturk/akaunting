<?php

namespace App\Observers\OAuth;

use App\Models\OAuth\Scope;
use Illuminate\Support\Facades\Cache;

class ScopeObserver
{
    /**
     * Handle the Scope "created" event.
     *
     * @param  \App\Models\OAuth\Scope  $scope
     * @return void
     */
    public function created(Scope $scope)
    {
        $this->clearOAuthScopeCache();
    }

    /**
     * Handle the Scope "updated" event.
     *
     * @param  \App\Models\OAuth\Scope  $scope
     * @return void
     */
    public function updated(Scope $scope)
    {
        $this->clearOAuthScopeCache();
    }

    /**
     * Handle the Scope "deleted" event.
     *
     * @param  \App\Models\OAuth\Scope  $scope
     * @return void
     */
    public function deleted(Scope $scope)
    {
        $this->clearOAuthScopeCache();
    }

    /**
     * Handle the Scope "restored" event.
     *
     * @param  \App\Models\OAuth\Scope  $scope
     * @return void
     */
    public function restored(Scope $scope)
    {
        $this->clearOAuthScopeCache();
    }

    /**
     * Clear OAuth scope cache
     *
     * @return void
     */
    protected function clearOAuthScopeCache()
    {
        Cache::forget('oauth.scopes');
        Cache::forget('oauth.default_scope');
    }
}
