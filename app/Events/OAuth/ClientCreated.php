<?php

namespace App\Events\OAuth;

use App\Models\OAuth\Client;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The client model.
     *
     * @var \App\Models\OAuth\Client
     */
    public $client;

    /**
     * Whether the client has a secret (confidential).
     *
     * @var bool
     */
    public $hasSecret;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\OAuth\Client  $client
     * @param  bool  $hasSecret
     * @return void
     */
    public function __construct(Client $client, bool $hasSecret = true)
    {
        $this->client = $client;
        $this->hasSecret = $hasSecret;
    }
}
