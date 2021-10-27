<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KeywordSearched
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $customerId;
    public $deviceId;
    public $keyword;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($customerId, $deviceId, $keyword)
    {
        $this->customerId = $customerId;
        $this->deviceId = $deviceId;
        $this->keyword = $keyword;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('keyword-searched');
    }
}
