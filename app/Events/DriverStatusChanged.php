<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $restaurantOrder;
    private $driverStatus;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($order, $driverStatus)
    {
        $this->order = $order;
        $this->driverStatus = $driverStatus;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('event.order-status-change');
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function getDriverStatus()
    {
        return $this->driverStatus;
    }
}
