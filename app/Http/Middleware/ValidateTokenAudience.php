<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;

class ValidateTokenAudience
{
    /**
     * The token repository instance.
     *
     * @var \Laravel\Passport\TokenRepository
     */
    protected $tokens;

    /**
     * Create a new middleware instance.
     *
     * @param  \Laravel\Passport\TokenRepository  $tokens
     * @return void
     */
    public function __construct(TokenRepository $tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * Handle an incoming request.
     *
     * MCP REQUIRED: Validate that the access token was issued specifically
     * for this resource server (RFC 8707 - Token Audience Validation).
     *
     * This prevents "confused deputy" attacks where tokens from one service
     * are used at another service.
     *
     * Reference: https://datatracker.ietf.org/doc/html/rfc8707
     * MCP Spec: https://modelcontextprotocol.io/specification/2025-06-18/basic/authorization
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip validation if OAuth is not enabled
        if (!config('oauth.enabled', false)) {
            return $next($request);
        }

        // Only validate if using OAuth authentication
        $authType = config('oauth.auth_type', 'basic');
        if ($authType !== 'passport') {
            return $next($request);
        }

        // Get the authenticated user via Passport
        $user = $request->user('passport');
        
        if (!$user) {
            // No authenticated user - let other middleware handle it
            return $next($request);
        }

        // Extract the access token from the request
        $token = $this->getAccessToken($request);

        if (!$token) {
            return $this->unauthorized('No access token provided', 'invalid_token');
        }

        // Validate token audience
        if (!$this->validateAudience($token)) {
            return $this->forbidden(
                'This access token was not issued for this resource server',
                'invalid_audience'
            );
        }

        return $next($request);
    }

    /**
     * Get the access token from the request.
     *
     * Uses Passport's built-in token resolution mechanism.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Laravel\Passport\Token|null
     */
    protected function getAccessToken(Request $request): ?Token
    {
        // Get authenticated user via Passport guard
        $user = $request->user('passport');
        
        if (!$user) {
            return null;
        }

        // Passport automatically resolves the token when authenticating
        // We can get it from the user's token() relationship
        try {
            // Get the current access token used for this request
            // Passport stores this in the request after authentication
            $psr = $request->server->get('psr_request') ?? $request;

            if (method_exists($user, 'token')) {
                return $user->token();
            }

            // Fallback: Parse bearer token and find in database
            $bearerToken = $request->bearerToken();
            if (!$bearerToken) {
                return null;
            }

            // Try to find token using Passport's TokenRepository
            $tokenId = $this->getTokenIdFromBearer($bearerToken);
            if ($tokenId) {
                return \App\Models\OAuth\AccessToken::find($tokenId);
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Failed to get access token', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null,
            ]);
            return null;
        }
    }

    /**
     * Extract token ID from bearer token (if JWT).
     *
     * @param  string  $bearerToken
     * @return string|null
     */
    protected function getTokenIdFromBearer(string $bearerToken): ?string
    {
        try {
            // JWT tokens have 3 parts separated by dots
            if (substr_count($bearerToken, '.') === 2) {
                // Parse JWT payload
                $parts = explode('.', $bearerToken);
                $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                
                // Return jti (JWT ID) which is the token ID in database
                return $payload['jti'] ?? null;
            }

            // For opaque tokens, the bearer token itself might be the ID
            return $bearerToken;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate that the token audience matches this server.
     *
     * MCP SPEC: Tokens MUST be bound to their intended resource server.
     *
     * @param  \Laravel\Passport\Token  $token
     * @return bool
     */
    protected function validateAudience(Token $token): bool
    {
        // Get token audience
        $tokenAudience = $token->audience ?? null;

        // If no audience is set on token, accept it (backward compatibility)
        // In strict mode, you might want to reject tokens without audience
        if (empty($tokenAudience)) {
            // Check if strict validation is enabled
            if (config('oauth.require_audience', false)) {
                return false; // Reject tokens without audience
            }
            return true; // Accept for backward compatibility
        }

        // Get expected audiences for this server
        $expectedAudiences = $this->getExpectedAudiences();

        // Check if token audience matches any expected audience
        foreach ($expectedAudiences as $expectedAudience) {
            if ($this->audienceMatches($tokenAudience, $expectedAudience)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get expected audience values for this server.
     *
     * @return array
     */
    protected function getExpectedAudiences(): array
    {
        $baseUrl = url('/');
        
        $audiences = [
            $baseUrl, // Main application URL
        ];

        // Add configured audiences if any
        $configuredAudiences = config('oauth.accepted_audiences', []);
        if (is_array($configuredAudiences) && !empty($configuredAudiences)) {
            $audiences = array_merge($audiences, $configuredAudiences);
        }

        return array_unique($audiences);
    }

    /**
     * Check if token audience matches expected audience.
     *
     * @param  string  $tokenAudience
     * @param  string  $expectedAudience
     * @return bool
     */
    protected function audienceMatches(string $tokenAudience, string $expectedAudience): bool
    {
        // Normalize URLs for comparison (remove trailing slashes, lowercase)
        $tokenAud = rtrim(strtolower($tokenAudience), '/');
        $expectedAud = rtrim(strtolower($expectedAudience), '/');

        return $tokenAud === $expectedAud;
    }

    /**
     * Return 401 Unauthorized response.
     *
     * @param  string  $message
     * @param  string  $error
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unauthorized(string $message, string $error = 'invalid_token')
    {
        return response()->json([
            'error' => $error,
            'error_description' => $message,
            'message' => $message,
        ], 401);
    }

    /**
     * Return 403 Forbidden response.
     *
     * @param  string  $message
     * @param  string  $error
     * @return \Illuminate\Http\JsonResponse
     */
    protected function forbidden(string $message, string $error = 'insufficient_scope')
    {
        return response()->json([
            'error' => $error,
            'error_description' => $message,
            'message' => $message,
        ], 403);
    }
}
