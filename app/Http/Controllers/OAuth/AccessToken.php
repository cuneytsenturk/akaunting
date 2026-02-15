<?php

namespace App\Http\Controllers\OAuth;

use Laravel\Passport\Http\Controllers\AccessTokenController as PassportAccessTokenController;
use Psr\Http\Message\ServerRequestInterface;

class AccessToken extends PassportAccessTokenController
{
    /**
     * Issue an access token.
     *
     * This controller handles the OAuth token endpoint.
     * MCP REQUIRED: Validates PKCE code_verifier for public clients.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $request
     * @return \Illuminate\Http\Response
     */
    public function issueToken(ServerRequestInterface $request)
    {
        // Set created_from for new tokens
        request()->merge(['created_from' => 'oauth.api']);

        // MCP REQUIRED: Extract and validate resource parameter (RFC 8707)
        $parsedBody = $request->getParsedBody();
        if (!empty($parsedBody['resource'])) {
            // Store resource in Laravel request for AccessToken model to use
            request()->merge(['resource' => $parsedBody['resource']]);
            
            // Validate resource format (must be valid URL)
            if (!filter_var($parsedBody['resource'], FILTER_VALIDATE_URL)) {
                return response()->json([
                    'error' => 'invalid_request',
                    'error_description' => 'The resource parameter must be a valid URL',
                    'error_uri' => 'https://datatracker.ietf.org/doc/html/rfc8707',
                ], 400);
            }
        }

        // MCP Compliance: Validate PKCE for public clients
        if (config('oauth.require_pkce', true)) {
            $this->validatePKCEIfRequired($request);
        }

        return parent::issueToken($request);
    }

    /**
     * Validate PKCE requirements for public clients.
     *
     * MCP REQUIRED: Public clients MUST use PKCE with S256 method.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $request
     * @return void
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function validatePKCEIfRequired(ServerRequestInterface $request): void
    {
        $parsedBody = $request->getParsedBody();
        $grantType = $parsedBody['grant_type'] ?? null;

        // Only check for authorization_code grant
        if ($grantType !== 'authorization_code') {
            return;
        }

        $clientId = $parsedBody['client_id'] ?? null;
        
        if (!$clientId) {
            return; // Let Passport handle missing client_id
        }

        // Check if client is confidential (has secret)
        $client = \App\Models\OAuth\Client::find($clientId);
        
        if (!$client) {
            return; // Let Passport handle invalid client
        }

        // Public clients (no secret) MUST use PKCE
        $isPublicClient = empty($client->secret);
        
        if ($isPublicClient && empty($parsedBody['code_verifier'])) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json([
                    'error' => 'invalid_request',
                    'error_description' => 'PKCE code_verifier is required for public clients (MCP compliance)',
                    'error_uri' => 'https://datatracker.ietf.org/doc/html/rfc7636',
                ], 400)
            );
        }
    }
}
