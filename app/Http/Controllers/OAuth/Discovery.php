<?php

namespace App\Http\Controllers\OAuth;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;

class Discovery extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // No authentication required for discovery endpoint
    }

    /**
     * Get OAuth 2.0 Authorization Server Metadata (RFC 8414).
     *
     * This endpoint provides metadata about the OAuth 2.0 authorization server.
     * Also known as the "well-known" endpoint for OAuth discovery.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function metadata(Request $request)
    {
        $baseUrl = url('/');
        $oauthPrefix = config('oauth.routes.prefix', 'oauth');
        $oauthUrl = "{$baseUrl}/{$oauthPrefix}";

        $metadata = [
            // Issuer identifier
            'issuer' => $baseUrl,

            // Authorization endpoint
            'authorization_endpoint' => url("/{$oauthPrefix}/authorize"),

            // Token endpoint
            'token_endpoint' => url("/{$oauthPrefix}/token"),

            // Token introspection endpoint (RFC 7662)
            'introspection_endpoint' => url("/{$oauthPrefix}/token/introspect"),

            // Token revocation endpoint (RFC 7009)
            'revocation_endpoint' => url("/{$oauthPrefix}/token/revoke"),

            // Scopes endpoint (custom)
            'scopes_endpoint' => url("/{$oauthPrefix}/scopes"),

            // Response types supported
            'response_types_supported' => [
                'code',           // Authorization code
                'token',          // Implicit (deprecated but listed)
            ],

            // Grant types supported
            'grant_types_supported' => [
                'authorization_code',
                'refresh_token',
                'client_credentials',
            ],

            // Token endpoint authentication methods
            'token_endpoint_auth_methods_supported' => [
                'client_secret_basic',
                'client_secret_post',
                'none', // MCP REQUIRED: For public clients with PKCE
            ],

            // Scopes supported
            'scopes_supported' => array_keys(config('oauth.scopes', [])),

            // Response modes supported
            'response_modes_supported' => [
                'query',
                'fragment',
            ],

            // Code challenge methods (PKCE) - MCP requires S256
            'code_challenge_methods_supported' => [
                'S256', // Required by MCP spec, plain is insecure
            ],

            // Dynamic Client Registration endpoint (RFC 7591) - recommended for MCP
            'registration_endpoint' => url("/{$oauthPrefix}/register"),

            // Revocation endpoint authentication methods
            'revocation_endpoint_auth_methods_supported' => [
                'client_secret_basic',
                'client_secret_post',
            ],

            // Introspection endpoint authentication methods
            'introspection_endpoint_auth_methods_supported' => [
                'client_secret_basic',
                'client_secret_post',
            ],

            // Service documentation
            'service_documentation' => config('app.url') . '/docs/oauth',

            // UI locales supported
            'ui_locales_supported' => array_keys(config('language.allowed', ['en-GB' => 'English'])),
        ];

        // Add password grant if enabled
        if (config('oauth.password_grant_client.enabled', false)) {
            $metadata['grant_types_supported'][] = 'password';
        }

        // Add company-aware custom metadata
        if (config('oauth.company_aware', true)) {
            $metadata['akaunting_company_aware'] = true;
            $metadata['akaunting_multi_tenant'] = true;
        }

        return response()->json($metadata, 200, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Get OpenID Connect Discovery metadata (optional).
     *
     * This provides OpenID Connect compatible discovery metadata.
     * Only needed if you plan to support OpenID Connect.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function openidConfiguration(Request $request)
    {
        $baseUrl = url('/');
        $oauthPrefix = config('oauth.routes.prefix', 'oauth');

        $metadata = [
            'issuer' => $baseUrl,
            'authorization_endpoint' => url("/{$oauthPrefix}/authorize"),
            'token_endpoint' => url("/{$oauthPrefix}/token"),
            'userinfo_endpoint' => url('/api/user'),
            'jwks_uri' => url("/{$oauthPrefix}/keys"),
            'scopes_supported' => array_keys(config('oauth.scopes', [])),
            'response_types_supported' => ['code', 'token', 'id_token', 'code token', 'code id_token', 'token id_token', 'code token id_token'],
            'grant_types_supported' => ['authorization_code', 'implicit', 'refresh_token'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
            'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post'],
            'claims_supported' => ['sub', 'name', 'email', 'email_verified'],
        ];

        return response()->json($metadata, 200, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Get OAuth 2.0 Protected Resource Metadata (RFC 9728).
     *
     * MCP REQUIRED: This endpoint tells MCP clients where to find the
     * authorization server and what scopes are supported.
     *
     * Spec: https://datatracker.ietf.org/doc/html/rfc9728
     * MCP: https://modelcontextprotocol.io/specification/2025-06-18/basic/authorization
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function protectedResourceMetadata(Request $request)
    {
        $baseUrl = url('/');
        $oauthPrefix = config('oauth.routes.prefix', 'oauth');

        $metadata = [
            // The canonical resource identifier (MUST match token audience)
            'resource' => $baseUrl,

            // Authorization server(s) that can issue tokens for this resource
            // MCP clients will use this to discover the authorization server
            'authorization_servers' => [$baseUrl],

            // Scopes that can be used with this resource
            'scopes_supported' => array_keys(config('oauth.scopes', ['mcp:use' => 'MCP Usage'])),

            // Optional: Link to documentation
            'resource_documentation' => config('app.url') . '/docs/oauth',

            // Optional: Token endpoint auth methods
            'token_endpoint_auth_methods_supported' => [
                'client_secret_basic',
                'client_secret_post',
                'none', // For public clients (PKCE)
            ],

            // Optional: How bearer tokens can be sent
            'bearer_methods_supported' => ['header'],

            // Optional: Token introspection endpoint
            'introspection_endpoint' => url("/{$oauthPrefix}/token/introspect"),

            // Optional: Token revocation endpoint
            'revocation_endpoint' => url("/{$oauthPrefix}/token/revoke"),

            // Akaunting specific metadata
            'resource_name' => config('app.name', 'Akaunting'),
            'resource_version' => version('short'),
        ];

        // Add company-aware information if enabled
        if (config('oauth.company_aware', true)) {
            $metadata['akaunting_company_aware'] = true;
            $metadata['akaunting_multi_tenant'] = true;
        }

        return response()->json($metadata, 200, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'public, max-age=3600',
            'Access-Control-Allow-Origin' => '*', // Allow CORS for discovery
        ]);
    }
}
