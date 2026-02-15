<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddOAuthWWWAuthenticateHeader
{
    /**
     * Handle an incoming request.
     *
     * MCP REQUIRED: Add WWW-Authenticate header to 401 responses.
     * This tells MCP clients where to find the OAuth discovery metadata.
     *
     * Reference: https://datatracker.ietf.org/doc/html/rfc9728#section-5.1
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only add header to 401 Unauthorized responses
        if ($response->getStatusCode() === 401) {
            $this->addWWWAuthenticateHeader($response, $request);
        }

        return $response;
    }

    /**
     * Add WWW-Authenticate header to response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function addWWWAuthenticateHeader(Response $response, Request $request): void
    {
        // Build the resource metadata URL
        $baseUrl = url('/');
        $oauthPrefix = config('oauth.routes.prefix', 'oauth');
        $resourceMetadataUrl = "{$baseUrl}/{$oauthPrefix}/.well-known/oauth-protected-resource";

        // Determine error details from response
        $error = 'invalid_token';
        $errorDescription = 'The access token is invalid or has expired';
        $scope = null;

        // Try to extract error from JSON response
        if ($response->headers->get('Content-Type') === 'application/json') {
            $content = json_decode($response->getContent(), true);
            if (isset($content['error'])) {
                $error = $content['error'];
            }
            if (isset($content['error_description'])) {
                $errorDescription = $content['error_description'];
            }
            if (isset($content['scope'])) {
                $scope = $content['scope'];
            }
        }

        // Build WWW-Authenticate header value
        // Format: Bearer resource_metadata="...", error="...", error_description="..."
        $headerParts = [
            'Bearer',
            'resource_metadata="' . $resourceMetadataUrl . '"',
            'error="' . $error . '"',
            'error_description="' . addslashes($errorDescription) . '"',
        ];

        // Add scope if available
        if ($scope) {
            $headerParts[] = 'scope="' . $scope . '"';
        }

        // Add realm (optional but recommended)
        $realm = config('app.name', 'Akaunting');
        $headerParts[] = 'realm="' . $realm . '"';

        $headerValue = implode(', ', $headerParts);

        // Set the WWW-Authenticate header
        $response->headers->set('WWW-Authenticate', $headerValue);
    }
}
