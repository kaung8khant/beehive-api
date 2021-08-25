<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderAssignEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $driver;
    public $time;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($order, $driver, $time)
    {
        $this->order = $order;
        $this->driver = $driver;
        $this->time = $time;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel("schedule.test");
    }
}
