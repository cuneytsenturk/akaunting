<?php

namespace App\Repositories\OAuth;

use Carbon\Carbon;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository as PassportTokenRepository;

/**
 * Company-scope–aware access token repository.
 *
 * The custom AccessToken model adds a global "company" scope that filters
 * rows by the currently active company. Token lookups performed by Passport
 * internally (e.g. `isAccessTokenRevoked` during bearer-token validation on
 * the /mcp endpoint) go through this repository. When no company context is
 * set – or the context does not match the token's company – the default
 * `find()` returns null, causing `isAccessTokenRevoked()` to return true and
 * every incoming request to fail with "Access token has been revoked."
 *
 * This repository bypasses the company scope wherever Passport performs an
 * internal lookup by token ID so that tokens are always found regardless of
 * the current request's company context.
 */
class TokenRepository extends PassportTokenRepository
{
    /**
     * Get a token by the given ID, ignoring any company scope.
     *
     * @param  string  $id
     * @return \Laravel\Passport\Token|null
     */
    public function find($id): ?Token
    {
        return Passport::token()
            ->withoutGlobalScope('company')
            ->where('id', $id)
            ->first();
    }

    /**
     * Get a token by the given user ID and token ID, ignoring the company
     * scope so the lookup is not restricted to the active company.
     *
     * @param  string  $id
     * @param  int  $userId
     * @return \Laravel\Passport\Token|null
     */
    public function findForUser($id, $userId): ?Token
    {
        return Passport::token()
            ->withoutGlobalScope('company')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Get all token instances for the given user ID, ignoring the company
     * scope.
     *
     * @param  mixed  $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function forUser($userId)
    {
        return Passport::token()
            ->withoutGlobalScope('company')
            ->where('user_id', $userId)
            ->get();
    }

    /**
     * Revoke an access token, ignoring the company scope so the update is
     * not missed when the active company differs from the token's company.
     *
     * @param  string  $id
     * @return mixed
     */
    public function revokeAccessToken($id): mixed
    {
        return Passport::token()
            ->withoutGlobalScope('company')
            ->where('id', $id)
            ->update(['revoked' => true]);
    }

    /**
     * Check if the access token has been revoked.
     *
     * Bypasses the company scope so that a token belonging to any company
     * can be verified during request authentication.
     *
     * @param  string  $id
     * @return bool
     */
    public function isAccessTokenRevoked($id): bool
    {
        $token = $this->find($id);

        if ($token) {
            return $token->revoked;
        }

        return true;
    }

    /**
     * Find a valid token instance for the given user and client, ignoring
     * the company scope.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  \Laravel\Passport\Client  $client
     * @return \Laravel\Passport\Token|null
     */
    public function getValidToken($user, $client): ?Token
    {
        return $client->tokens()
            ->withoutGlobalScope('company')
            ->whereUserId($user->getAuthIdentifier())
            ->where('revoked', 0)
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }
}
