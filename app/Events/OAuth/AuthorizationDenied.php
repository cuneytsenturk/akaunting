<?php

namespace App\Events\OAuth;

use App\Models\OAuth\Client;
use App\Models\Auth\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuthorizationDenied
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
     * Create a new event instance.
     *
     * @param  \App\Models\OAuth\Client  $client
     * @param  \App\Models\Auth\User  $user
     * @return void
     */
    public function __construct(Client $client, User $user)
    {
        $this->client = $client;
        $this->user = $user;
    }
}
