<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OAuth 2.0 Authentication
    |--------------------------------------------------------------------------
    |
    | This option controls whether OAuth 2.0 authentication is enabled for
    | your application. When disabled, the system will use the traditional
    | basic authentication method for API requests.
    |
    */

    'enabled' => env('OAUTH_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | API Authentication Type
    |--------------------------------------------------------------------------
    |
    | This option determines which authentication method will be used for
    | API requests. Available options are:
    |
    | - "basic": Traditional basic authentication (default)
    | - "passport": OAuth 2.0 authentication via Laravel Passport
    |
    | You can switch between these methods without affecting existing API
    | endpoints or breaking changes.
    |
    */

    'auth_type' => env('API_AUTH_TYPE', 'basic'),

    /*
    |--------------------------------------------------------------------------
    | OAuth Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Define custom route paths for OAuth endpoints. These routes will be
    | registered within your application and can be customized to match
    | your preferred URL structure.
    |
    */

    'routes' => [
        'prefix' => env('OAUTH_ROUTE_PREFIX', 'oauth'),
        'authorize' => env('OAUTH_AUTHORIZE_PATH', 'authorize'),
        'token' => env('OAUTH_TOKEN_PATH', 'token'),
        'tokens' => env('OAUTH_TOKENS_PATH', 'tokens'),
        'clients' => env('OAUTH_CLIENTS_PATH', 'clients'),
        'scopes' => env('OAUTH_SCOPES_PATH', 'scopes'),
        'personal_access_tokens' => env('OAUTH_PERSONAL_TOKENS_PATH', 'personal-access-tokens'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Views
    |--------------------------------------------------------------------------
    |
    | Specify custom views for OAuth pages. This allows you to completely
    | control the UI/UX of authorization pages, token management, etc.
    |
    */

    'views' => [
        'authorize' => env('OAUTH_AUTHORIZE_VIEW', 'auth.oauth.authorize'),
        'clients' => env('OAUTH_CLIENTS_VIEW', 'auth.oauth.clients'),
        'tokens' => env('OAUTH_TOKENS_VIEW', 'auth.oauth.tokens'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Company-Aware OAuth
    |--------------------------------------------------------------------------
    |
    | Enable company-aware OAuth tokens. When enabled, all OAuth tokens
    | will be associated with a specific company ID, ensuring proper
    | multi-tenancy support.
    |
    */

    'company_aware' => env('OAUTH_COMPANY_AWARE', true),

    /*
    |--------------------------------------------------------------------------
    | Token Expiration
    |--------------------------------------------------------------------------
    |
    | Define how long access tokens and refresh tokens should remain valid.
    | Values are in minutes. You can also set these to null to make tokens
    | never expire (not recommended for production).
    |
    */

    'expiration' => [
        'access_token' => env('OAUTH_ACCESS_TOKEN_LIFETIME', 60), // 1 hour
        'refresh_token' => env('OAUTH_REFRESH_TOKEN_LIFETIME', 20160), // 14 days
        'personal_access_token' => env('OAUTH_PERSONAL_ACCESS_TOKEN_LIFETIME', 525600), // 1 year
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Guards
    |--------------------------------------------------------------------------
    |
    | Define which guards should be used for different token types.
    | This allows you to use different guards for web and API authentication.
    |
    */

    'guards' => [
        'web' => env('OAUTH_WEB_GUARD', 'web'),
        'api' => env('OAUTH_API_GUARD', 'passport'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Client UUIDs
    |--------------------------------------------------------------------------
    |
    | Enable or disable UUIDs for OAuth clients. When enabled, clients
    | will use UUIDs instead of auto-incrementing IDs.
    |
    */

    'client_uuids' => env('OAUTH_CLIENT_UUIDS', false),

    /*
    |--------------------------------------------------------------------------
    | Personal Access Client
    |--------------------------------------------------------------------------
    |
    | Personal access clients are used for generating personal access tokens
    | without going through the full OAuth flow.
    |
    */

    'personal_access_client' => [
        'enabled' => env('OAUTH_PERSONAL_ACCESS_ENABLED', true),
        'id' => env('OAUTH_PERSONAL_ACCESS_CLIENT_ID'),
        'secret' => env('OAUTH_PERSONAL_ACCESS_CLIENT_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Grant Client
    |--------------------------------------------------------------------------
    |
    | Password grant clients are used for first-party applications where
    | you can trust the client with user credentials.
    |
    */

    'password_grant_client' => [
        'enabled' => env('OAUTH_PASSWORD_GRANT_ENABLED', false),
        'id' => env('OAUTH_PASSWORD_GRANT_CLIENT_ID'),
        'secret' => env('OAUTH_PASSWORD_GRANT_CLIENT_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    |
    | Define OAuth scopes for your application. Scopes allow you to limit
    | the access granted to client applications.
    |
    */

    'scopes' => [
        // Example scopes - customize as needed
        'read' => 'Read access to your data',
        'write' => 'Write access to your data',
        'admin' => 'Full administrative access',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Scope
    |--------------------------------------------------------------------------
    |
    | The default scope to use when no specific scope is requested.
    |
    */

    'default_scope' => env('OAUTH_DEFAULT_SCOPE'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware that should be applied to OAuth routes.
    |
    */

    'middleware' => [
        'web' => ['web', 'auth', 'company.identify'],
        'api' => explode(',', env('OAUTH_API_MIDDLEWARE', 'api,auth:api')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Hash Client Secrets
    |--------------------------------------------------------------------------
    |
    | When enabled, client secrets will be hashed in the database using
    | bcrypt. This provides an extra layer of security.
    |
    */

    'hash_client_secrets' => env('OAUTH_HASH_CLIENT_SECRETS', false),

];
