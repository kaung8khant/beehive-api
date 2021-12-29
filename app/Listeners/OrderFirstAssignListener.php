<?php

namespace App\Listeners;

use App\Events\OrderAssignEvent;
use App\Models\RestaurantBranch;
use App\Repositories\Driver\DriverRealtimeDataRepositoryInterface;
use App\Repositories\OrderDriver\RestaurantOrderDriverStatusRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\OneSignalService\NotificationServiceInterface;
use Illuminate\Queue\InteractsWithQueue;

class OrderFirstAssignListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The time (seconds) before the job should be processed.
     *
     * @var int
     */
    public $delay = 0;

    // public $connection = 'database';
    private $repository;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(RestaurantOrderDriverStatusRepositoryInterface $repository, DriverRealtimeDataRepositoryInterface $driverRealtime, NotificationServiceInterface $oneSignal)
    {
        $this->repository = $repository;
        $this->driverRealtime = $driverRealtime;
        $this->oneSignal = $oneSignal;
    }

    /**
     * Handle the event.
     *
     * @param  OrderAssignEvent  $event
     * @return void
     */
    public function handle(OrderAssignEvent $event)
    {

        if (count($event->driver) == 0) {

            $restaurantBranch = RestaurantBranch::where('slug', $event->order->restaurant_branch_info['slug'])->first();

            $driver = $this->driverRealtime->getAvailableDrivers($event->driver);

            $driver = $this->driverRealtime->sortDriverByLocation($restaurantBranch, $driver);

            $driverData = array_keys($driver);
            $driverSlug = count($driverData) > 0 ? $driverData[0] : null;

            $assignedDriver = $event->driver;
            array_push($assignedDriver, $driverSlug);

            if (count($driverData) > 1 && !$this->repository->checkOrderAccepted($event->order)) {
                event(new OrderAssignEvent($event->order, $assignedDriver, $event->time + 1));
            } else {
                // send notification to admin for max assignment or no driver
            }

            if (isset($driverSlug)) {

                $this->repository->assignDriver($event->order, $driverSlug);
                $this->oneSignal->sendDriverNotification(
                    array($driverSlug),
                    'You have received new order. Accept Now!',
                    $event->order
                );
                $this->repository->setJobToFirebase($event->order->slug, $driverSlug);
            }
        }
    }
}
