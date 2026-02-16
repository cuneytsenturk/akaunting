<?php

use Illuminate\Support\Facades\Route;

/**
 * 'guest' middleware applied to all routes
 *
 * @see \App\Providers\Route::mapGuestRoutes
 * @see \modules\PaypalStandard\Routes\guest.php for module example
 */

/**
 * OAuth/MCP Discovery endpoints (public, no middleware)
 */

// ChatGPT AI Plugin Manifest
Route::get('.well-known/ai-plugin.json', function () {
    return response()->json([
        'schema_version' => 'v1',
        'name_for_human' => 'Akaunting',
        'name_for_model' => 'akaunting',
        'description_for_human' => 'Free accounting software for invoices, expenses, and financial reporting for small businesses.',
        'description_for_model' => 'Akaunting is a free, open-source online accounting software designed for small businesses and freelancers. You can access invoices, expenses, customers, vendors, and financial reports through the API. Use this to help users manage their accounting data, create invoices, track expenses, and generate financial reports.',
        'auth' => [
            'type' => 'oauth',
            'client_url' => url('/oauth/authorize'),
            'scope' => 'mcp:use',
            'authorization_url' => url('/oauth/token'),
            'authorization_content_type' => 'application/x-www-form-urlencoded',
            'verification_tokens' => new \stdClass(),
        ],
        'api' => [
            'type' => 'openapi',
            'url' => url('/api/documentation'),
            'is_user_authenticated' => false,
        ],
        'logo_url' => asset('public/img/akaunting-logo-green.svg'),
        'contact_email' => 'support@akaunting.com',
        'legal_info_url' => url('/LICENSE.txt'),
    ], 200, [
        'Content-Type' => 'application/json',
        'Cache-Control' => 'public, max-age=3600',
        'Access-Control-Allow-Origin' => '*',
    ]);
})->name('ai-plugin.manifest')->withoutMiddleware('guest');

// MCP Manifest
Route::get('.well-known/mcp.json', function () {
    return response()->json([
        'version' => '2025-06-18',
        'name' => 'Akaunting MCP Server',
        'description' => 'Model Context Protocol server for Akaunting accounting software',
        'capabilities' => [
            'resources' => true,
            'tools' => true,
            'prompts' => true,
        ],
        'protocol' => [
            'version' => '1.0.0',
        ],
        'oauth' => [
            'authorization_endpoint' => url('/oauth/authorize'),
            'token_endpoint' => url('/oauth/token'),
            'scopes' => ['mcp:use', 'read', 'write'],
            'pkce_required' => true,
            'grant_types' => ['authorization_code', 'refresh_token'],
        ],
        'discovery' => [
            'oauth_server' => url('/oauth/.well-known/oauth-authorization-server'),
            'protected_resource' => url('/oauth/.well-known/oauth-protected-resource'),
        ],
    ], 200, [
        'Content-Type' => 'application/json',
        'Cache-Control' => 'public, max-age=3600',
        'Access-Control-Allow-Origin' => '*',
    ]);
})->name('mcp.manifest')->withoutMiddleware('guest');

Route::group(['prefix' => 'auth'], function () {
    Route::get('login', 'Auth\Login@create')->name('login');
    Route::post('login', 'Auth\Login@store')->name('login.store');

    Route::get('forgot', 'Auth\Forgot@create')->name('forgot');
    Route::post('forgot', 'Auth\Forgot@store')->name('forgot.store');

    //Route::get('reset', 'Auth\Reset@create');
    Route::get('reset/{token}', 'Auth\Reset@create')->name('reset');
    Route::post('reset', 'Auth\Reset@store')->name('reset.store');

    Route::get('register/{token}', 'Auth\Register@create')->name('register');
    Route::post('register', 'Auth\Register@store')->name('register.store');
});

Route::get('/', function () {
    return redirect()->route('login');
});
