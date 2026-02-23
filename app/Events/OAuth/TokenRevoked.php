<?php

namespace App\Events\OAuth;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TokenRevoked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The token ID.
     *
     * @var string
     */
    public $tokenId;

    /**
     * The client ID.
     *
     * @var string|null
     */
    public $clientId;

    /**
     * The user ID.
     *
     * @var int|null
     */
    public $userId;

    /**
     * Additional metadata.
     *
     * @var array
     */
    public $metadata;

    /**
     * Create a new event instance.
     *
     * @param  string  $tokenId
     * @param  string|null  $clientId
     * @param  int|null  $userId
     * @param  array  $metadata
     * @return void
     */
    public function __construct(string $tokenId, ?string $clientId = null, ?int $userId = null, array $metadata = [])
    {
        $this->tokenId = $tokenId;
        $this->clientId = $clientId;
        $this->userId = $userId;
        $this->metadata = $metadata;
    }
}
