<?php

namespace App\Events\OAuth;

use App\Models\OAuth\Client;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The client model.
     *
     * @var \App\Models\OAuth\Client
     */
    public $client;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\OAuth\Client  $client
     * @return void
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }
}
