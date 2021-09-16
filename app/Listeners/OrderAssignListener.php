<?php

namespace App\Listeners;

use App\Events\OrderAssignEvent;
use App\Models\RestaurantBranch;
use App\Repositories\Abstracts\DriverRealtimeDataRepositoryInterface;
use App\Repositories\Abstracts\RestaurantOrderDriverStatusRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OrderAssignListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The time (seconds) before the job should be processed.
     *
     * @var int
     */
    public $delay = 5;

    // public $connection = 'database';
    private $repository;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(RestaurantOrderDriverStatusRepositoryInterface $repository, DriverRealtimeDataRepositoryInterface $driverRealtime)
    {
        $this->repository = $repository;
        $this->driverRealtime = $driverRealtime;
    }

    /**
     * Handle the event.
     *
     * @param  OrderAssignEvent  $event
     * @return void
     */
    public function handle(OrderAssignEvent $event)
    {
        $maxAssign = 5;

        $restaurantBranch = RestaurantBranch::where('slug', $event->order->restaurant_branch_info['slug'])->first();

        $driver = $this->driverRealtime->getAvailableDrivers($event->driver);

        $driver = $this->driverRealtime->sortDriverByLocation($restaurantBranch, $driver);

        $driverData = array_keys($driver);
        $driverSlug = count($driverData) > 0 ? $driverData[0] : null;

        $assignedDriver = $event->driver;
        array_push($assignedDriver, $driverSlug);

        if ($event->time + 1 < $maxAssign && count($driverData) > 1) {
            event(new OrderAssignEvent($event->order, $assignedDriver, $event->time + 1));
        } else {
            // send notification to admin for max assignment or no driver
        }

        if (isset($driverSlug)) {
            $this->repository->assignDriver($event->order, $driverSlug);
        }
    }
}
