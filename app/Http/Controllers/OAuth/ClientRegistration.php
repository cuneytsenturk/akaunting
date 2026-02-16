<?php

namespace App\Http\Controllers\OAuth;

use App\Abstracts\Http\Controller;
use App\Http\Requests\OAuth\ClientRegistration as ClientRegistrationRequest;
use App\Models\OAuth\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClientRegistration extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // No authentication required for public client registration
        // Rate limiting is applied via middleware
    }

    /**
     * Register a new OAuth client dynamically (RFC 7591).
     *
     * MCP REQUIRED: ChatGPT and other MCP clients use this endpoint to
     * automatically register themselves without manual intervention.
     *
     * Reference: https://datatracker.ietf.org/doc/html/rfc7591
     *
     * @param  \App\Http\Requests\OAuth\ClientRegistration  $request
     * @return \Illuminate\Http\Response
     */
    public function register(ClientRegistrationRequest $request)
    {
        try {
            // Validation handled by ClientRegistrationRequest
            $validated = $request->validated();

            // Create the client
            $client = $this->createClient($validated);

            // Build response according to RFC 7591
            $response = $this->buildRegistrationResponse($client);

            return response()->json($response, 201, [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-store',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'invalid_client_metadata',
                'error_description' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'server_error',
                'error_description' => 'An error occurred during client registration',
            ], 500);
        }
    }

    /**
     * Validate redirect URI according to OAuth 2.1 security best practices.
     *
     * @param  string  $uri
     * @return bool
     */
    protected function isValidRedirectUri(string $uri): bool
    {
        // Parse URI
        $parsed = parse_url($uri);

        if (!$parsed || !isset($parsed['scheme']) || !isset($parsed['host'])) {
            return false;
        }

        $scheme = $parsed['scheme'];
        $host = $parsed['host'];

        // Allow localhost for development
        if (in_array($host, ['localhost', '127.0.0.1', '[::1]'])) {
            return in_array($scheme, ['http', 'https']);
        }

        // Production must use HTTPS
        if ($scheme !== 'https') {
            return false;
        }

        // Check against whitelisted domains if configured
        $allowedDomains = config('oauth.dcr.allowed_domains', []);
        
        if (!empty($allowedDomains)) {
            foreach ($allowedDomains as $allowedDomain) {
                if ($host === $allowedDomain || str_ends_with($host, '.' . $allowedDomain)) {
                    return true;
                }
            }
            return false;
        }

        // URI cannot contain fragment
        if (isset($parsed['fragment'])) {
            return false;
        }

        return true;
    }

    /**
     * Create the OAuth client.
     *
     * @param  array  $validated
     * @return \App\Models\OAuth\Client
     */
    protected function createClient(array $validated): Client
    {
        $isConfidential = $validated['token_endpoint_auth_method'] !== 'none';

        // Create client
        $client = new Client();
        $client->name = $validated['client_name'];
        $client->redirect = json_encode($validated['redirect_uris']);
        $client->personal_access_client = false;
        $client->password_client = false;
        $client->revoked = false;

        // Generate secret for confidential clients
        if ($isConfidential) {
            $plainSecret = Str::random(40);
            
            if (config('oauth.hash_client_secrets', false)) {
                $client->secret = password_hash($plainSecret, PASSWORD_BCRYPT);
            } else {
                $client->secret = $plainSecret;
            }
            
            // Store plain secret temporarily for response
            $client->plain_secret = $plainSecret;
        }

        // Set company ID if company-aware
        if (config('oauth.company_aware', true)) {
            // For DCR, use system company or first available
            $client->company_id = company_id() ?? 1;
        }

        // Set metadata
        $client->created_from = 'oauth.dcr';
        $client->created_by = null; // No user for public registration

        $client->save();

        return $client;
    }

    /**
     * Build the registration response according to RFC 7591.
     *
     * @param  \App\Models\OAuth\Client  $client
     * @return array
     */
    protected function buildRegistrationResponse(Client $client): array
    {
        $redirectUris = json_decode($client->redirect, true);

        $response = [
            'client_id' => (string) $client->id,
            'client_id_issued_at' => $client->created_at->timestamp,
            'redirect_uris' => $redirectUris,
            'client_name' => $client->name,
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'token_endpoint_auth_method' => $client->secret ? 'client_secret_post' : 'none',
        ];

        // Add client secret for confidential clients (only in response, never again)
        if (isset($client->plain_secret)) {
            $response['client_secret'] = $client->plain_secret;
            $response['client_secret_expires_at'] = 0; // Never expires
        }

        // Add registration access token (optional - for client management)
        if (config('oauth.dcr.enable_management', false)) {
            $registrationToken = Str::random(64);
            // Store this token for later client updates/deletes
            // TODO: Implement token storage
            
            $response['registration_access_token'] = $registrationToken;
            $response['registration_client_uri'] = url("/oauth/register/{$client->id}");
        }

        return $response;
    }

    /**
     * Get client information (RFC 7591 - Client Read).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $clientId
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $clientId)
    {
        // TODO: Validate registration_access_token
        
        $client = Client::findOrFail($clientId);

        $response = [
            'client_id' => (string) $client->id,
            'client_id_issued_at' => $client->created_at->timestamp,
            'redirect_uris' => json_decode($client->redirect, true),
            'client_name' => $client->name,
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'token_endpoint_auth_method' => $client->secret ? 'client_secret_post' : 'none',
        ];

        return response()->json($response);
    }

    /**
     * Update client information (RFC 7591 - Client Update).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $clientId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $clientId)
    {
        // TODO: Validate registration_access_token
        // TODO: Implement client update logic
        
        return response()->json([
            'error' => 'not_implemented',
            'error_description' => 'Client update is not yet implemented',
        ], 501);
    }

    /**
     * Delete client (RFC 7591 - Client Delete).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $clientId
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $clientId)
    {
        // TODO: Validate registration_access_token
        
        $client = Client::findOrFail($clientId);
        $client->revoked = true;
        $client->save();

        return response()->json(null, 204);
    }
}
