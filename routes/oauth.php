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

// Authorization Endpoints (require auth)
Route::get('authorize', 'OAuth\Authorize@show')
    ->name('oauth.authorize.show')
    ->withoutMiddleware('oauth')
    ->middleware(['web', 'auth', 'throttle:oauth']);

Route::post('authorize', 'OAuth\Authorize@approve')
    ->name('oauth.authorize.approve')
    ->withoutMiddleware('oauth')
    ->middleware(['web', 'auth', 'throttle:oauth']);

Route::group(['as' => 'oauth.'], function () {
    // Token Management (User's personal tokens)

    // Token Management (User's personal tokens)
    Route::get('tokens', 'OAuth\Token@index')->name('tokens.index');
    Route::delete('tokens/{token_id}', 'OAuth\Token@destroy')->name('tokens.destroy');

    // Client Management
    Route::get('clients', 'OAuth\Client@index')->name('clients.index');
    Route::get('clients/create', 'OAuth\Client@create')->name('clients.create');
    Route::post('clients', 'OAuth\Client@store')->name('clients.store');
    Route::get('clients/{client}', 'OAuth\Client@show')->name('clients.show');
    Route::get('clients/{client}/edit', 'OAuth\Client@edit')->name('clients.edit');
    Route::patch('clients/{client}', 'OAuth\Client@update')->name('clients.update');
    Route::delete('clients/{client}', 'OAuth\Client@destroy')->name('clients.destroy');
    Route::post('clients/{client}/secret', 'OAuth\Client@secret')->name('clients.secret');

    // Personal Access Tokens
    Route::post('personal-access-tokens', 'OAuth\PersonalAccessToken@store')->name('personal.tokens.store');
    Route::delete('personal-access-tokens/{token_id}', 'OAuth\PersonalAccessToken@destroy')->name('personal.tokens.destroy');

    // Scopes (API - read only)
    Route::get('scopes', 'OAuth\Scope@index')->name('scopes.index');
});
