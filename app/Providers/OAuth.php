<?php

namespace App\Providers;

use App\Models\OAuth\AccessToken;
use App\Models\OAuth\AuthCode;
use App\Models\OAuth\Client;
use App\Models\OAuth\PersonalAccessClient;
use App\Models\OAuth\RefreshToken;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class OAuth extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * This method configures Laravel Passport to use Akaunting's
     * company-aware OAuth models instead of the default Passport models.
     *
     * @return void
     */
    public function boot()
    {
        // Only configure Passport if OAuth is enabled
        if (! config('oauth.enabled', false)) {
            return;
        }

        // Use custom models with company_id support
        Passport::useClientModel(Client::class);
        Passport::useTokenModel(AccessToken::class);
        Passport::useRefreshTokenModel(RefreshToken::class);
        Passport::useAuthCodeModel(AuthCode::class);
        Passport::usePersonalAccessClientModel(PersonalAccessClient::class);

        // Disable default Passport routes (we'll use custom routes)
        if (! config('passport.register_routes', false)) {
            Passport::ignoreRoutes();
        }

        // Set token expiration from config
        Passport::tokensExpireIn(
            now()->addMinutes(config('oauth.expiration.access_token', 60))
        );

        Passport::refreshTokensExpireIn(
            now()->addMinutes(config('oauth.expiration.refresh_token', 20160))
        );

        Passport::personalAccessTokensExpireIn(
            now()->addMinutes(config('oauth.expiration.personal_access_token', 525600))
        );

        // Register OAuth scopes
        $scopes = config('oauth.scopes', []);
        if (!empty($scopes)) {
            Passport::tokensCan($scopes);
        }

        // Set default scope for OAuth tokens
        if ($defaultScope = config('oauth.default_scope')) {
            Passport::setDefaultScope($defaultScope);
        }

        // Enable client hashing if configured
        if (config('oauth.hash_client_secrets', false) || config('passport.hash_client_secrets', false)) {
            Passport::hashClientSecrets();
        }

        // Enable cookie serialization
        Passport::cookie(config('passport.cookie', 'laravel_token'));

        // Load migrations from package if needed
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(database_path('migrations'));
        }
    }
}
