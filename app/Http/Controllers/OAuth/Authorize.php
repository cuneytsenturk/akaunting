<?php

namespace App\Http\Controllers\OAuth;

use App\Abstracts\Http\Controller;
use App\Events\OAuth\AuthorizationApproved;
use App\Events\OAuth\AuthorizationDenied;
use App\Http\Requests\OAuth\AuthorizeRequest;
use App\Models\OAuth\Client;
use App\Services\OAuth\ScopeMapper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Laravel\Passport\Bridge\User as BridgeUser;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\TokenRepository;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\AuthorizationServer;
use Nyholm\Psr7\Response as Psr7Response;

class Authorize extends Controller
{
    /**
     * The authorization server.
     *
     * @var \League\OAuth2\Server\AuthorizationServer
     */
    protected $server;

    /**
     * Create a new controller instance.
     *
     * @param  \League\OAuth2\Server\AuthorizationServer  $server
     * @return void
     */
    public function __construct(AuthorizationServer $server)
    {
        //parent::__construct();

        $this->server = $server;

        $this->middleware('auth');
    }

    /**
     * Show the authorization approval prompt.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $psrRequest
     * @param  \Illuminate\Http\Request  $request
     * @param  \Laravel\Passport\ClientRepository  $clients
     * @param  \Laravel\Passport\TokenRepository  $tokens
     * @return \Illuminate\Http\Response
     */
    public function show(ServerRequestInterface $psrRequest, Request $request, ClientRepository $clients, TokenRepository $tokens)
    {
        try {
            $authRequest = $this->server->validateAuthorizationRequest($psrRequest);

            $scopes = $this->parseScopes($authRequest);
            $client = $clients->find($authRequest->getClient()->getIdentifier());
            $user = $request->user();

            // Filter requested scopes to only those the user is eligible to grant.
            // Users cannot delegate permissions they don't have themselves.
            // mcp:use and other manual scopes pass through unconditionally.
            $scopes = $user->filterGrantableScopes($scopes);

            // MCP REQUIRED: Extract resource parameter (RFC 8707)
            // This will be used as the token audience
            $queryParams = $psrRequest->getQueryParams();
            $resourceIdentifier = $queryParams['resource'] ?? url('/');
            
            // Store resource identifier in session for token issuance
            $request->session()->put('oauth.resource', $resourceIdentifier);

            // Get user's companies
            $companies = $user->companies()->enabled()->get();

            // If company_aware is enabled, filter by client's company
            if (config('oauth.company_aware', true) && $client->company_id) {
                $companies = $companies->where('id', $client->company_id);
                
                if ($companies->isEmpty()) {
                    abort(403, trans('general.error.not_in_company'));
                }
            }

            // Convert companies to select options format with logo and details
            $companyOptions = [];
            $originalCompany = company();
            
            foreach ($companies as $company) {
                $company->makeCurrent();
                
                $logo = null;
                $logoMedia = $company->getMedia('company.logo')->last();
                if ($logoMedia) {
                    $logo = $logoMedia->getUrl();
                }
                
                $companyOptions[$company->id] = [
                    'id' => $company->id,
                    'name' => setting('company.name', $company->domain),
                    'email' => setting('company.email', ''),
                    'logo' => $logo,
                    'domain' => $company->domain,
                ];
            }
            
            // Restore original company
            if ($originalCompany) {
                $originalCompany->makeCurrent();
            }

            // Auto-select if only one company available
            $selectedCompanyId = null;
            if (count($companyOptions) === 1) {
                $selectedCompanyId = array_key_first($companyOptions);
            }

            // Check if client already authorized by this user for any of their companies
            if ($client->skipsAuthorization() || $this->hasValidToken($tokens, $user, $client, $scopes)) {
                // If auto-approved, use first available company
                if ($selectedCompanyId) {
                    $request->session()->put('oauth.company_id', $selectedCompanyId);
                }

                // Fire event so listeners (e.g. free plan assignment) still run on auto-approval path
                event(new AuthorizationApproved($client, $user, $scopes));

                return $this->approveRequest($authRequest, $user);
            }

            $request->session()->put('authToken', $token = Str::random());
            $request->session()->put('authRequest', $authRequest);

            // Build scope objects with descriptions for the authorization view
            $scopeObjects = collect($scopes)->map(fn (string $key) => (object) [
                'id'          => $key,
                'description' => ScopeMapper::describe($key),
            ])->all();

            return $this->response('oauth.authorize', [
                'client'            => $client,
                'user'              => $user,
                'scopes'            => $scopeObjects,
                'request'           => $request,
                'authToken'         => $token,
                'companies'         => $companyOptions,
                'selectedCompanyId' => $selectedCompanyId,
            ]);
        } catch (\League\OAuth2\Server\Exception\OAuthServerException $e) {
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Approve the authorization request.
     *
     * @param  \App\Http\Requests\OAuth\AuthorizeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function approve(AuthorizeRequest $request)
    {
        $authToken = $request->session()->get('authToken');

        if ($authToken !== $request->input('auth_token')) {
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => trans('general.invalid_token'),
            ], 401);
        }

        // Validation handled by AuthorizeRequest
        $companyId = $request->input('company_id');
        $user = $request->user();

        // Store company_id in session for token creation
        $request->session()->put('oauth.company_id', $companyId);

        $authRequest = $request->session()->get('authRequest');

        // Get client for event
        $client = Client::find($authRequest->getClient()->getIdentifier());
        $scopes = $this->parseScopes($authRequest);

        $authRequest->setUser(new BridgeUser($user->getAuthIdentifier()));
        $authRequest->setAuthorizationApproved(true);

        $response = $this->convertResponse(
            $this->server->completeAuthorizationRequest($authRequest, new Psr7Response())
        );

        // Fire event
        if ($client) {
            event(new AuthorizationApproved($client, $user, $scopes));
        }

        // Clean up session
        $request->session()->forget('oauth.company_id');

        return $response;
    }

    /**
     * Deny the authorization request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deny(Request $request)
    {
        $authRequest = $request->session()->get('authRequest');

        // Get client for event
        $client = Client::find($authRequest->getClient()->getIdentifier());
        $user = $request->user();

        $authRequest->setAuthorizationApproved(false);

        // Fire event
        if ($client && $user) {
            event(new AuthorizationDenied($client, $user));
        }

        return $this->convertResponse(
            $this->server->completeAuthorizationRequest($authRequest, new Psr7Response())
        );
    }

    /**
     * Check if the user has a valid token for the given client and scopes.
     *
     * @param  \Laravel\Passport\TokenRepository  $tokens
     * @param  mixed  $user
     * @param  \App\Models\OAuth\Client  $client
     * @param  array  $scopes
     * @return bool
     */
    protected function hasValidToken($tokens, $user, $client, $scopes)
    {
        $token = $tokens->findValidToken($user, $client);

        if (!$token) {
            return false;
        }

        $tokenScopes = $token->scopes;

        return count($scopes) === 0 || (count(array_diff($scopes, $tokenScopes)) === 0);
    }

    /**
     * Parse the scopes from the authorization request.
     *
     * @param  \League\OAuth2\Server\RequestTypes\AuthorizationRequest  $authRequest
     * @return array
     */
    protected function parseScopes($authRequest)
    {
        return collect($authRequest->getScopes())->map(function ($scope) {
            return $scope->getIdentifier();
        })->all();
    }

    /**
     * Approve the request for the current user.
     *
     * @param  \League\OAuth2\Server\RequestTypes\AuthorizationRequest  $authRequest
     * @param  mixed  $user
     * @return \Illuminate\Http\Response
     */
    protected function approveRequest($authRequest, $user)
    {
        $authRequest->setUser(new BridgeUser($user->getAuthIdentifier()));
        $authRequest->setAuthorizationApproved(true);

        return $this->convertResponse(
            $this->server->completeAuthorizationRequest($authRequest, new Psr7Response())
        );
    }

    /**
     * Convert a PSR7 response to an Illuminate Response.
     *
     * @param  \Psr\Http\Message\ResponseInterface  $psrResponse
     * @return \Illuminate\Http\Response
     */
    protected function convertResponse($psrResponse)
    {
        return new \Illuminate\Http\Response(
            $psrResponse->getBody(),
            $psrResponse->getStatusCode(),
            $psrResponse->getHeaders()
        );
    }
}
