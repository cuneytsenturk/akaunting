<?php

namespace App\Traits;

use App\Models\OAuth\AccessToken;
use App\Traits\Users;

trait Companies
{
    use Users;

    public $request = null;

    public function getCompanyId()
    {
        if ($company_id = company_id()) {
            return $company_id;
        }

        $request = $this->request ?: request();

        // Treat OAuth bearer-token requests as API requests so that:
        //  1. company_id can be extracted from the access token itself, and
        //  2. The user's first company is used as a fallback (instead of abort 500).
        // This handles MCP endpoints like /mcp that are not under the api/* prefix.
        if (request_is_api($request) || request_is_mcp($request)) {
            return $this->getCompanyIdFromApi($request);
        }

        return $this->getCompanyIdFromWeb($request);
    }

    public function getCompanyIdFromWeb($request)
    {
        return $this->getCompanyIdFromRoute($request) ?: ($this->getCompanyIdFromQuery($request) ?: $this->getCompanyIdFromHeader($request));
    }

    public function getCompanyIdFromApi($request)
    {
        // Priority 1: OAuth Token (must be first since it doesn't rely on session or route)
        $company_id = $this->getCompanyIdFromToken($request);

        // Priority 2: Query string (?company_id=2)
        if (! $company_id) {
            $company_id = $this->getCompanyIdFromQuery($request);
        }

        // Priority 3: Header (X-Company: 2)
        if (! $company_id) {
            $company_id = $this->getCompanyIdFromHeader($request);
        }

        // Priority 4: User'ın ilk company'si (fallback)
        // user() uses auth()->user() which checks the default (web) guard.
        // For OAuth requests (e.g. /mcp), only the 'passport' guard is set by
        // auth.oauth.once — the web guard is never populated. So we must resolve
        // the user from the passport guard explicitly before falling back to
        // getFirstCompanyOfUser().
        if (! $company_id) {
            $apiUser = auth()->guard('passport')->user() ?? user();

            if ($apiUser) {
                $company = $apiUser->withoutEvents(fn () => $apiUser->companies()->enabled()->first());

                $company_id = $company?->id;
            }
        }

        return $company_id;
    }

    /**
     * Get company ID from OAuth access token.
     *
     * When a user is authenticated via OAuth (Passport), extract the company_id
     * that was assigned to the token during authorization approval.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int|null
     */
    protected function getCompanyIdFromToken($request): ?int
    {
        // Check if OAuth is enabled and user is authenticated via Passport
        if (! config('oauth.enabled', false)) {
            logger()->debug('OAuth token: Disabled, skipping token company_id extraction.');

            return null;
        }

        try {
            // Try passport guard first
            if (auth()->guard('passport')->check()) {
                $user = auth()->guard('passport')->user();

                if ($user && method_exists($user, 'token')) {
                    $token = $user->token();

                    if ($token && isset($token->company_id) && $token->company_id) {
                        logger()->debug('OAuth token: Extracted company_id from token: ' . $token->company_id);

                        return (int) $token->company_id;
                    }

                    // Token exists but no company_id stored — use the passport user's first company
                    logger()->debug('OAuth token: No company_id on token, falling back to passport user\'s first company.');
                    $company = $user->withoutEvents(fn () => $user->companies()->enabled()->first());
                    if ($company) {
                        return (int) $company->id;
                    }

                    logger()->debug('OAuth token: No companies found for authenticated passport user.');
                }

                logger()->debug('OAuth token: No company_id found on authenticated user\'s token.');
            }

            // Fallback: Try to get token from request directly
            if ($request->bearerToken()) {
                $tokenId = $request->bearerToken();

                // Try to find the token in database
                $tokenModel = config('oauth.company_aware', true)
                    ? AccessToken::withoutGlobalScope('company')->where('id', $tokenId)->first()
                    : null;

                if ($tokenModel && isset($tokenModel->company_id)) {
                    logger()->debug('OAuth token: Extracted company_id from token model: ' . $tokenModel->company_id);

                    return (int) $tokenModel->company_id;
                }

                logger()->debug('OAuth token: No company_id found on token model for bearer token.');
            }
        } catch (\Exception $e) {
            // Silently fail if OAuth token checking fails
            // This allows fallback to other methods
            logger()->debug('OAuth token: company_id extraction failed: ' . $e->getMessage());
        }

        logger()->debug('OAuth token: No company_id found in token.');

        return null;
    }

    public function getCompanyIdFromRoute($request)
    {
        $route_id = (int) $request->route('company_id');
        $segment_id = (int) $request->segment(1);

        return $route_id ?: $segment_id;
    }

    public function getCompanyIdFromQuery($request)
    {
        return (int) $request->query('company_id');
    }

    public function getCompanyIdFromHeader($request)
    {
        return (int) $request->header('X-Company');
    }
}
