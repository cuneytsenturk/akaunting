<?php

namespace App\Events\OAuth;

use App\Models\OAuth\Client;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientSecretRegenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The client model.
     *
     * @var \App\Models\OAuth\Client
     */
    public $client;

    /**
     * The new client secret (plain text).
     *
     * @var string
     */
    public $newSecret;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\OAuth\Client  $client
     * @param  string  $newSecret
     * @return void
     */
    public function __construct(Client $client, string $newSecret)
    {
        $this->client = $client;
        $this->newSecret = $newSecret;
    }
}
