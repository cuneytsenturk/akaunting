<?php

use Illuminate\Support\Facades\Route;

/**
 * 'auth' middleware applied to all routes
 *
 * @see \App\Providers\Route::mapOAuthRoutes
 */

Route::group(['as' => 'oauth.', 'prefix' => 'oauth'], function () {
    // Authorization Endpoints
    Route::get('authorize', 'OAuth\Authorize@show')->name('authorize.show');
    Route::post('authorize', 'OAuth\Authorize@approve')->name('authorize.approve');
    Route::delete('authorize', 'OAuth\Authorize@deny')->name('authorize.deny');

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

// OAuth Token Endpoint (stateless, no auth required - handled by Passport)
Route::group(['prefix' => 'oauth'], function () {
    Route::post('token', 'OAuth\AccessToken@issueToken')->name('oauth.token');
});
