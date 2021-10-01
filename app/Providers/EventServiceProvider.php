<?php

namespace App\Providers;

use App\Events\DataChanged;
use App\Events\DriverStatusChanged;
use App\Events\OrderAssignEvent;
use App\Listeners\OrderAssignListener;
use App\Listeners\StoreAuditInformation;
use App\Listeners\UpdateOrderStatus;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        DriverStatusChanged::class => [
            UpdateOrderStatus::class,
        ],
        OrderAssignEvent::class => [
            OrderAssignListener::class,
        ],
        DataChanged::class => [
            StoreAuditInformation::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
