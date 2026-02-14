<?php

namespace App\Http\Controllers\OAuth;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\AccessTokenController as PassportAccessTokenController;
use Psr\Http\Message\ServerRequestInterface;

class AccessToken extends PassportAccessTokenController
{
    /**
     * Issue an access token.
     *
     * This controller handles the OAuth token endpoint.
     * It's stateless and doesn't require authentication.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $request
     * @return \Illuminate\Http\Response
     */
    public function issueToken(ServerRequestInterface $request)
    {
        // Set created_from for new tokens
        request()->merge(['created_from' => 'oauth.api']);

        return parent::issueToken($request);
    }
}
