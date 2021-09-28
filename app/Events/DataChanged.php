<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DataChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $action;
    public $table;
    public $slug;
    public $url;
    public $status;
    public $request;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user, $action, $table, $slug, $status, $request = null)
    {
        $this->user = $user;
        $this->action = $action;
        $this->table = $table;
        $this->slug = $slug;
        $this->url = $request->url();
        $this->status = $status;
        $this->request = $request->all();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('data-changed');
    }
}
