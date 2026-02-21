<?php

namespace App\Repositories\OAuth;

use Illuminate\Support\Facades\Log;
use Laravel\Passport\Passport;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\RefreshTokenRepository as PassportRefreshTokenRepository;

/**
 * Company-scope–aware refresh token repository.
 *
 * Passport's default RefreshTokenRepository uses the model's query builder
 * directly (Passport::refreshToken()->where(...)). Our custom RefreshToken
 * model adds a global "company" scope that filters by the currently active
 * company. During a stateless /oauth/token request the company context may
 * already be set (e.g. carried over from the web session used in the
 * authorization step), causing `find()` to match nothing and therefore
 * making `isRefreshTokenRevoked()` return true – which Passport then
 * translates into "The refresh token is invalid."
 *
 * This repository bypasses the company scope for every operation that
 * Passport performs internally on refresh tokens so that tokens are always
 * found regardless of the current company context.
 */
class RefreshTokenRepository extends PassportRefreshTokenRepository
{
    /**
     * Find a refresh token by its ID, ignoring any company scope.
     *
     * @param  string  $id
     * @return \Laravel\Passport\RefreshToken|null
     */
    public function find($id): ?RefreshToken
    {
        return Passport::refreshToken()
            ->withoutGlobalScope('company')
            ->where('id', $id)
            ->first();
    }

    /**
     * Revoke a refresh token, ignoring any company scope.
     *
     * @param  string  $id
     * @return mixed
     */
    public function revokeRefreshToken($id): mixed
    {
        return Passport::refreshToken()
            ->withoutGlobalScope('company')
            ->where('id', $id)
            ->update(['revoked' => true]);
    }

    /**
     * Revoke all refresh tokens for a given access token ID,
     * ignoring any company scope.
     *
     * @param  string  $tokenId
     * @return mixed
     */
    public function revokeRefreshTokensByAccessTokenId($tokenId): mixed
    {
        return Passport::refreshToken()
            ->withoutGlobalScope('company')
            ->where('access_token_id', $tokenId)
            ->update(['revoked' => true]);
    }

    /**
     * Check whether a refresh token has been revoked.
     *
     * Bypasses the company scope so that a token belonging to any company
     * can be verified.
     *
     * @param  string  $id
     * @return bool
     */
    public function isRefreshTokenRevoked($id): bool
    {
        $token = $this->find($id);

        if ($token) {
            if ($token->revoked) {
                Log::warning('OAuth: Refresh token is revoked', [
                    'token_id'   => substr($id, 0, 8) . '...',
                    'company_id' => $token->company_id,
                    'expires_at' => $token->expires_at,
                    'context_company_id' => company_id(),
                ]);
            }

            return $token->revoked;
        }

        // Token not found – could be soft-deleted or filtered by a scope
        $withTrashed = Passport::refreshToken()
            ->withoutGlobalScope('company')
            ->withTrashed()
            ->where('id', $id)
            ->first();

        Log::warning('OAuth: Refresh token not found', [
            'token_id'          => substr($id, 0, 8) . '...',
            'context_company_id' => company_id(),
            'found_with_trashed' => $withTrashed !== null,
            'trashed_revoked'    => $withTrashed?->revoked,
            'trashed_deleted_at' => $withTrashed?->deleted_at,
            'trashed_company_id' => $withTrashed?->company_id,
        ]);

        return true;
    }
}
