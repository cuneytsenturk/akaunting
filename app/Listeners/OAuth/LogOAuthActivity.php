<?php

namespace App\Listeners\OAuth;

use App\Events\OAuth\AuthorizationApproved;
use App\Events\OAuth\AuthorizationDenied;
use App\Events\OAuth\ClientCreated;
use App\Events\OAuth\ClientDeleted;
use App\Events\OAuth\ClientSecretRegenerated;
use App\Events\OAuth\ClientUpdated;
use App\Events\OAuth\TokenCreated;
use App\Events\OAuth\TokenRevoked;
use App\Models\OAuth\ActivityLog;
use Illuminate\Support\Facades\Auth;

class LogOAuthActivity
{
    /**
     * Handle token created events.
     *
     * @param  \App\Events\OAuth\TokenCreated  $event
     * @return void
     */
    public function handleTokenCreated(TokenCreated $event)
    {
        $token = $event->token;
        $client = $event->client;

        ActivityLog::logActivity([
            'company_id' => session('company_id') ?? company_id(),
            'user_id' => $token->user_id ?? Auth::id(),
            'event_type' => 'token.created',
            'resource_type' => 'token',
            'resource_id' => $token->id ?? null,
            'client_name' => $client?->name,
            'client_id' => $client?->id ?? $token->client_id ?? null,
            'token_id' => $token->id ?? null,
            'scopes' => $token->scopes ?? [],
            'description' => trans('oauth.activity.token_created', [
                'client' => $client?->name ?? 'Unknown Client',
            ]),
            'metadata' => array_merge([
                'expires_at' => $token->expires_at ?? null,
                'grant_type' => $event->metadata['grant_type'] ?? null,
            ], $event->metadata),
        ]);
    }

    /**
     * Handle token revoked events.
     *
     * @param  \App\Events\OAuth\TokenRevoked  $event
     * @return void
     */
    public function handleTokenRevoked(TokenRevoked $event)
    {
        ActivityLog::logActivity([
            'company_id' => session('company_id') ?? company_id(),
            'user_id' => $event->userId ?? Auth::id(),
            'event_type' => 'token.revoked',
            'resource_type' => 'token',
            'client_id' => $event->clientId,
            'token_id' => $event->tokenId,
            'description' => trans('oauth.activity.token_revoked'),
            'metadata' => $event->metadata,
        ]);
    }

    /**
     * Handle client created events.
     *
     * @param  \App\Events\OAuth\ClientCreated  $event
     * @return void
     */
    public function handleClientCreated(ClientCreated $event)
    {
        $client = $event->client;

        ActivityLog::logActivity([
            'company_id' => $client->company_id,
            'user_id' => $client->user_id ?? Auth::id(),
            'event_type' => 'client.created',
            'resource_type' => 'client',
            'resource_id' => $client->id,
            'client_name' => $client->name,
            'client_id' => $client->id,
            'description' => trans('oauth.activity.client_created', [
                'name' => $client->name,
            ]),
            'metadata' => [
                'confidential' => $event->hasSecret,
                'redirect' => $client->redirect,
                'personal_access_client' => $client->personal_access_client,
                'password_client' => $client->password_client,
            ],
        ]);
    }

    /**
     * Handle client updated events.
     *
     * @param  \App\Events\OAuth\ClientUpdated  $event
     * @return void
     */
    public function handleClientUpdated(ClientUpdated $event)
    {
        $client = $event->client;

        ActivityLog::logActivity([
            'company_id' => $client->company_id,
            'user_id' => $client->user_id ?? Auth::id(),
            'event_type' => 'client.updated',
            'resource_type' => 'client',
            'resource_id' => $client->id,
            'client_name' => $client->name,
            'client_id' => $client->id,
            'description' => trans('oauth.activity.client_updated', [
                'name' => $client->name,
            ]),
            'metadata' => [
                'changes' => $client->getChanges(),
                'original' => $event->original,
            ],
        ]);
    }

    /**
     * Handle client deleted events.
     *
     * @param  \App\Events\OAuth\ClientDeleted  $event
     * @return void
     */
    public function handleClientDeleted(ClientDeleted $event)
    {
        $client = $event->client;

        ActivityLog::logActivity([
            'company_id' => $client->company_id,
            'user_id' => $client->user_id ?? Auth::id(),
            'event_type' => 'client.deleted',
            'resource_type' => 'client',
            'resource_id' => $client->id,
            'client_name' => $client->name,
            'client_id' => $client->id,
            'description' => trans('oauth.activity.client_deleted', [
                'name' => $client->name,
            ]),
        ]);
    }

    /**
     * Handle client secret regenerated events.
     *
     * @param  \App\Events\OAuth\ClientSecretRegenerated  $event
     * @return void
     */
    public function handleClientSecretRegenerated(ClientSecretRegenerated $event)
    {
        $client = $event->client;

        ActivityLog::logActivity([
            'company_id' => $client->company_id,
            'user_id' => $client->user_id ?? Auth::id(),
            'event_type' => 'client.secret.regenerated',
            'resource_type' => 'client',
            'resource_id' => $client->id,
            'client_name' => $client->name,
            'client_id' => $client->id,
            'description' => trans('oauth.activity.client_secret_regenerated', [
                'name' => $client->name,
            ]),
        ]);
    }

    /**
     * Handle authorization approved events.
     *
     * @param  \App\Events\OAuth\AuthorizationApproved  $event
     * @return void
     */
    public function handleAuthorizationApproved(AuthorizationApproved $event)
    {
        ActivityLog::logActivity([
            'company_id' => session('company_id') ?? company_id(),
            'user_id' => $event->user->id,
            'event_type' => 'authorization.approved',
            'resource_type' => 'authorization',
            'client_name' => $event->client->name,
            'client_id' => $event->client->id,
            'scopes' => $event->scopes,
            'description' => trans('oauth.activity.authorization_approved', [
                'client' => $event->client->name,
            ]),
        ]);
    }

    /**
     * Handle authorization denied events.
     *
     * @param  \App\Events\OAuth\AuthorizationDenied  $event
     * @return void
     */
    public function handleAuthorizationDenied(AuthorizationDenied $event)
    {
        ActivityLog::logActivity([
            'company_id' => session('company_id') ?? company_id(),
            'user_id' => $event->user->id,
            'event_type' => 'authorization.denied',
            'resource_type' => 'authorization',
            'client_name' => $event->client->name,
            'client_id' => $event->client->id,
            'description' => trans('oauth.activity.authorization_denied', [
                'client' => $event->client->name,
            ]),
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     * @return void
     */
    public function subscribe($events)
    {
        $events->listen(
            TokenCreated::class,
            [LogOAuthActivity::class, 'handleTokenCreated']
        );

        $events->listen(
            TokenRevoked::class,
            [LogOAuthActivity::class, 'handleTokenRevoked']
        );

        $events->listen(
            ClientCreated::class,
            [LogOAuthActivity::class, 'handleClientCreated']
        );

        $events->listen(
            ClientUpdated::class,
            [LogOAuthActivity::class, 'handleClientUpdated']
        );

        $events->listen(
            ClientDeleted::class,
            [LogOAuthActivity::class, 'handleClientDeleted']
        );

        $events->listen(
            ClientSecretRegenerated::class,
            [LogOAuthActivity::class, 'handleClientSecretRegenerated']
        );

        $events->listen(
            AuthorizationApproved::class,
            [LogOAuthActivity::class, 'handleAuthorizationApproved']
        );

        $events->listen(
            AuthorizationDenied::class,
            [LogOAuthActivity::class, 'handleAuthorizationDenied']
        );
    }
}
