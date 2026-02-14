<?php

namespace App\Traits;

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

        if (request_is_api($request)) {
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
        // Priority 1: OAuth Token'dan al (en güvenli ve kullanıcı dostu)
        $company_id = $this->getCompanyIdFromToken($request);

        // Priority 2: Query string'den al (?company_id=2)
        if (!$company_id) {
            $company_id = $this->getCompanyIdFromQuery($request);
        }

        // Priority 3: Header'dan al (X-Company: 2)
        if (!$company_id) {
            $company_id = $this->getCompanyIdFromHeader($request);
        }

        // Priority 4: User'ın ilk company'si (fallback)
        if (!$company_id) {
            $company_id = $this->getFirstCompanyOfUser()?->id;
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
    protected function getCompanyIdFromToken($request)
    {
        // Check if OAuth is enabled and user is authenticated via Passport
        if (!config('oauth.enabled', false)) {
            return null;
        }

        try {
            // Try passport guard first
            if (auth()->guard('passport')->check()) {
                $user = auth()->guard('passport')->user();

                if ($user && method_exists($user, 'token')) {
                    $token = $user->token();

                    if ($token && isset($token->company_id)) {
                        return (int) $token->company_id;
                    }
                }
            }

            // Fallback: Try to get token from request directly
            if ($request->bearerToken()) {
                $tokenId = $request->bearerToken();

                // Try to find the token in database
                $tokenModel = config('oauth.company_aware', true) 
                    ? \App\Models\OAuth\AccessToken::withoutGlobalScope('company')->where('id', $tokenId)->first()
                    : null;

                if ($tokenModel && isset($tokenModel->company_id)) {
                    return (int) $tokenModel->company_id;
                }
            }
        } catch (\Exception $e) {
            // Silently fail if OAuth token checking fails
            // This allows fallback to other methods
            \Log::debug('OAuth token company_id extraction failed: ' . $e->getMessage());
        }

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
