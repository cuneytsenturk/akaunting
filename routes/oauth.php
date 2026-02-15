<?php

use Illuminate\Support\Facades\Route;

/**
 * 'auth' middleware applied to all routes
 *
 * @see \App\Providers\Route::mapOAuthRoutes
 */

// OAuth Token Endpoint (stateless, no auth required - handled by Passport)
Route::post('token', 'OAuth\AccessToken@issueToken')
    ->name('oauth.token')
    ->withoutMiddleware('oauth')
    ->middleware(['throttle:oauth', 'bindings']);

// Token Introspection Endpoint (RFC 7662)
Route::post('token/introspect', 'OAuth\Token@introspect')
    ->name('oauth.token.introspect')
    ->middleware(['throttle:oauth', 'bindings']);

// Token Revocation Endpoint (RFC 7009)
Route::post('token/revoke', 'OAuth\Token@revoke')
    ->name('oauth.token.revoke')
    ->middleware(['throttle:oauth', 'bindings']);

// Authorization Endpoints (require auth)
Route::get('authorize', 'OAuth\Authorize@show')
    ->name('oauth.authorize.show')
    ->withoutMiddleware('oauth')
    ->middleware(['web', 'auth', 'throttle:oauth']);

Route::post('authorize', 'OAuth\Authorize@approve')
    ->name('oauth.authorize.approve')
    ->withoutMiddleware('oauth')
    ->middleware(['web', 'auth', 'throttle:oauth']);

Route::delete('authorize', 'OAuth\Authorize@deny')
    ->name('oauth.authorize.deny')
    ->withoutMiddleware('oauth')
    ->middleware(['web', 'auth', 'throttle:oauth']);

// OAuth Discovery Endpoint (RFC 8414)
Route::get('.well-known/oauth-authorization-server', 'OAuth\Discovery@metadata')
    ->name('oauth.metadata')
    ->withoutMiddleware('oauth');

// Protected Resource Metadata Endpoint (RFC 9728) - MCP REQUIRED
Route::get('.well-known/oauth-protected-resource', 'OAuth\Discovery@protectedResourceMetadata')
    ->name('oauth.protected-resource-metadata')
    ->withoutMiddleware('oauth');

// OpenID Connect Discovery (optional)
Route::get('.well-known/openid-configuration', 'OAuth\Discovery@openidConfiguration')
    ->name('oauth.openid.configuration')
    ->withoutMiddleware('oauth');

// Dynamic Client Registration (RFC 7591) - MCP REQUIRED
Route::post('register', 'OAuth\ClientRegistration@register')
    ->name('oauth.register')
    ->withoutMiddleware('oauth')
    ->middleware(['throttle:dcr', 'bindings']);

Route::group(['as' => 'oauth.'], function () {
    // Token Management (User's personal tokens)
    Route::get('tokens', 'OAuth\Token@index')->name('tokens.index');
    Route::delete('tokens/{token_id}', 'OAuth\Token@destroy')->name('tokens.destroy');

    // Client Management (Authorized Applications)
    Route::get('clients', 'OAuth\Clients@index')->name('clients.index');
    Route::get('clients/{client}', 'OAuth\Clients@show')->name('clients.show');
    Route::post('clients/{client}/revoke', 'OAuth\Clients@revoke')->name('clients.revoke');
    Route::delete('clients/{client}', 'OAuth\Clients@destroy')->name('clients.destroy');

    // Personal Access Tokens
    Route::post('personal-access-tokens', 'OAuth\PersonalAccessToken@store')->name('personal.tokens.store');
    Route::delete('personal-access-tokens/{token_id}', 'OAuth\PersonalAccessToken@destroy')->name('personal.tokens.destroy');

    // Scopes (API - read only)
    Route::get('scopes', 'OAuth\Scope@index')->name('scopes.index');
});
