<?php

namespace App\Providers;

use App\Events\CustomerLoggedIn;
use App\Events\DataChanged;
use App\Events\DriverStatusChanged;
use App\Events\KeywordSearched;
use App\Events\OrderAssignEvent;
use App\Listeners\MergeSearchHistory;
use App\Listeners\OrderAssignListener;
use App\Listeners\OrderFirstAssignListener;
use App\Listeners\StoreAuditInformation;
use App\Listeners\StoreSearchHistory;
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
            OrderFirstAssignListener::class,
            OrderAssignListener::class,
        ],
        DataChanged::class => [
            StoreAuditInformation::class,
        ],
        KeywordSearched::class => [
            StoreSearchHistory::class,
        ],
        CustomerLoggedIn::class => [
            MergeSearchHistory::class,
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
