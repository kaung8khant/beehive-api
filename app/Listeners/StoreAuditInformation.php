<?php

namespace App\Listeners;

use App\Events\DataChanged;
use App\Models\Audit;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

class StoreAuditInformation implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  DataChanged  $event
     * @return void
     */
    public function handle(DataChanged $event)
    {
        $table = $event->table;
        $message = $event->user->name . ' ' . $event->action . ' ' . $event->table . ' ' . $event->slug;

        if ($event->action === 'upload') {
            $table = 'files';
            $message = $event->user->name . ' ' . $event->action . ' files ' . $event->request['slug'] . ' to ' . $event->table . ' ' . $event->slug;
        }

        if ($event->table === 'files') {
            $message = $event->user->name . ' ' . $event->action . ' files ' . $event->request['slug'] . ' from ' . $event->request['source'] . ' ' . $event->slug;
        }

        Audit::create([
            'user_slug' => $event->user->slug,
            'username' => $event->user->username,
            'action' => $event->action,
            'table' => $table,
            'url' => $event->url,
            'status' => $event->status,
            'request' => $event->request,
            'message' => $message,
        ]);
    }
}
