<?php

namespace App\Events\OAuth;

use App\Models\OAuth\Client;
use App\Models\Auth\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuthorizationApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The client model.
     *
     * @var \App\Models\OAuth\Client
     */
    public $client;

    /**
     * The user model.
     *
     * @var \App\Models\Auth\User
     */
    public $user;

    /**
     * The requested scopes.
     *
     * @var array
     */
    public $scopes;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\OAuth\Client  $client
     * @param  \App\Models\Auth\User  $user
     * @param  array  $scopes
     * @return void
     */
    public function __construct(Client $client, User $user, array $scopes = [])
    {
        $this->client = $client;
        $this->user = $user;
        $this->scopes = $scopes;
    }
}
