<?php

namespace App\Events\OAuth;

use App\Models\OAuth\Client;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TokenCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The token model.
     *
     * @var object
     */
    public $token;

    /**
     * The client model.
     *
     * @var \App\Models\OAuth\Client|null
     */
    public $client;

    /**
     * Additional metadata.
     *
     * @var array
     */
    public $metadata;

    /**
     * Create a new event instance.
     *
     * @param  object  $token
     * @param  \App\Models\OAuth\Client|null  $client
     * @param  array  $metadata
     * @return void
     */
    public function __construct($token, ?Client $client = null, array $metadata = [])
    {
        $this->token = $token;
        $this->client = $client;
        $this->metadata = $metadata;
    }
}
