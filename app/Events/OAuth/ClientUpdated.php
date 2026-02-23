<?php

namespace App\Events\OAuth;

use App\Models\OAuth\Client;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The client model.
     *
     * @var \App\Models\OAuth\Client
     */
    public $client;

    /**
     * The original attributes before update.
     *
     * @var array
     */
    public $original;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\OAuth\Client  $client
     * @param  array  $original
     * @return void
     */
    public function __construct(Client $client, array $original = [])
    {
        $this->client = $client;
        $this->original = $original;
    }
}
