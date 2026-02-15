<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Passport Guard
    |--------------------------------------------------------------------------
    |
    | This configuration option defines the authentication guard that will
    | be used to protect your API routes. This guard will be used when
    | authenticating API requests using OAuth 2.0.
    |
    */

    'guard' => env('PASSPORT_GUARD', 'web'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Keys
    |--------------------------------------------------------------------------
    |
    | Passport uses encryption keys from your Laravel application. You can
    | customize the paths to your keys here if needed.
    |
    */

    'private_key' => env('PASSPORT_PRIVATE_KEY'),

    'public_key' => env('PASSPORT_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Client UUIDs
    |--------------------------------------------------------------------------
    |
    | By default, Passport uses auto-incrementing primary keys when assigning
    | IDs to clients. However, if Passport is installed using the --uuids
    | switch, this will be set to "true" and UUIDs will be used instead.
    |
    */

    'client_uuids' => env('PASSPORT_CLIENT_UUIDS', false),

    /*
    |--------------------------------------------------------------------------
    | Personal Access Client
    |--------------------------------------------------------------------------
    |
    | If you enable client hashing, you should set the personal access client
    | ID and unhashed secret within your environment file. The values will
    | be used when issuing new personal access tokens to your users.
    |
    */

    'personal_access_client' => [
        'id' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_ID'),
        'secret' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Grant Client
    |--------------------------------------------------------------------------
    |
    | If you enable client hashing, you should set the password grant client
    | ID and unhashed secret within your environment file. The values will
    | be used for the password grant authentication flow.
    |
    */

    'password_client' => [
        'id' => env('PASSPORT_PASSWORD_CLIENT_ID'),
        'secret' => env('PASSPORT_PASSWORD_CLIENT_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Passport Storage Driver
    |--------------------------------------------------------------------------
    |
    | This configuration option defines the storage driver that will be used
    | to store encrypted tokens in the database. By default, the database
    | driver is used which will store tokens in your database.
    |
    */

    'storage' => [
        'database' => [
            'connection' => env('PASSPORT_DB_CONNECTION', config('database.default')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Expiration
    |--------------------------------------------------------------------------
    |
    | Here you may define the number of minutes that a token should remain
    | valid for. This is used as a default value, but you can override it
    | when creating tokens programmatically.
    |
    */

    'tokens_expire_in' => env('PASSPORT_ACCESS_TOKEN_EXPIRE', 60),

    'refresh_tokens_expire_in' => env('PASSPORT_REFRESH_TOKEN_EXPIRE', 20160),

    'personal_access_tokens_expire_in' => env('PASSPORT_PERSONAL_ACCESS_TOKEN_EXPIRE', 525600),

    /*
    |--------------------------------------------------------------------------
    | Hash Client Secrets
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, the client secrets will be hashed in the
    | database using bcrypt. This will prevent client secrets from being
    | revealed even if your database is compromised.
    |
    */

    'hash_client_secrets' => env('PASSPORT_HASH_CLIENT_SECRETS', false),

    /*
    |--------------------------------------------------------------------------
    | Passport Cookie Name
    |--------------------------------------------------------------------------
    |
    | This configuration option determines the name of the cookie that will
    | be attached to responses from the server when issuing tokens.
    |
    */

    'cookie' => env('PASSPORT_COOKIE_NAME', 'laravel_token'),

    /*
    |--------------------------------------------------------------------------
    | Passport Routes
    |--------------------------------------------------------------------------
    |
    | This option controls if Passport should automatically register its
    | routes. Set to false to disable automatic route registration and
    | use custom routes instead (recommended for Akaunting).
    |
    */

    'register_routes' => env('PASSPORT_REGISTER_ROUTES', false),

    /*
    |--------------------------------------------------------------------------
    | Passport Middleware
    |--------------------------------------------------------------------------
    |
    | This array defines middleware that should be applied to Passport routes
    | if automatic route registration is enabled. When using custom routes,
    | apply middleware directly to your route definitions.
    |
    */

    'middleware' => [
        'web' => ['web', 'auth', 'company.identify'],
        'api' => ['api', 'auth:api', 'company.identify'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignore Routes
    |--------------------------------------------------------------------------
    |
    | This option allows you to customize which default Passport routes
    | should be ignored when registering routes. Useful when you want
    | to register some routes but not others.
    |
    */

    'ignore_routes' => [
        // List of route names to ignore
    ],

    /*
    |--------------------------------------------------------------------------
    | PKCE (Proof Key for Code Exchange)
    |--------------------------------------------------------------------------
    |
    | MCP REQUIRED: Enable PKCE for enhanced security. When enabled, public
    | clients MUST use PKCE with S256 code challenge method. This is a
    | requirement for OAuth 2.1 and MCP specification compliance.
    |
    | Reference: https://datatracker.ietf.org/doc/html/rfc7636
    |
    */

    'require_pkce' => env('PASSPORT_REQUIRE_PKCE', true),

    /*
    |--------------------------------------------------------------------------
    | Allow Plain PKCE
    |--------------------------------------------------------------------------
    |
    | MCP REQUIRES S256 only. Set to false to enforce S256 code challenge.
    | Plain method is considered insecure and should not be used.
    |
    */

    'allow_plain_pkce' => env('PASSPORT_ALLOW_PLAIN_PKCE', false),

];
