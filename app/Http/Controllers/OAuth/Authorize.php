<?php

namespace App\Http\Controllers\OAuth;

use App\Abstracts\Http\Controller;
use App\Models\OAuth\Client;
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
        parent::__construct();

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

            // Check if client belongs to current company
            if (config('oauth.company_aware', true) && $client->company_id !== company_id()) {
                abort(403, trans('general.error.not_in_company'));
            }

            // Check if client already authorized by this user
            if ($client->skipsAuthorization() || $this->hasValidToken($tokens, $user, $client, $scopes)) {
                return $this->approveRequest($authRequest, $user);
            }

            $request->session()->put('authToken', $token = Str::random());
            $request->session()->put('authRequest', $authRequest);

            return $this->response('auth.oauth.authorize', [
                'client' => $client,
                'user' => $user,
                'scopes' => $scopes,
                'request' => $request,
                'authToken' => $token,
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function approve(Request $request)
    {
        $authToken = $request->session()->get('authToken');

        if ($authToken !== $request->input('auth_token')) {
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => trans('general.invalid_token'),
            ], 401);
        }

        $authRequest = $request->session()->get('authRequest');
        $user = $request->user();

        $authRequest->setUser(new BridgeUser($user->getAuthIdentifier()));
        $authRequest->setAuthorizationApproved(true);

        return $this->convertResponse(
            $this->server->completeAuthorizationRequest($authRequest, new Psr7Response())
        );
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

        $authRequest->setAuthorizationApproved(false);

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
