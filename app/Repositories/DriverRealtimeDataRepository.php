<?php

namespace App\Repositories;

use App\Helpers\GeoHelper;
use App\Models\User;
use App\Repositories\Abstracts\DriverRealtimeDataRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DriverRealtimeDataRepository implements DriverRealtimeDataRepositoryInterface
{
    use GeoHelper;

    private $database;

    public function __construct()
    {
        $this->database = app('firebase.database');
    }

    public function updateDriverStatus($driverSlug, $status)
    {
        $this->database->getReference("/drivers/${driverSlug}")
            ->set($status);
    }

    public function getAvailableDrivers($exclude = [])
    {
        $drivers = $this->database->getReference('/driver')
            ->orderByChild('updated_at')
            // enable the following code if you want real-time active data.
            ->startAt(Carbon::now()->subMinutes(1)->toDateTimeString())
            ->getSnapshot()->getValue();

        $filterDriver =  array_filter($drivers, function ($obj, $key) use ($exclude) {
            if ($obj['last_order'] == 'accepted' || $obj['last_order'] == 'delivered') {
                if (in_array($key, $exclude)) {
                    return false;
                }
                return User::where('slug', $key)
                    ->where('is_enable', true)
                    ->where('is_locked', false)
                    ->exists();
            }
            return false;
        }, ARRAY_FILTER_USE_BOTH);


        return $filterDriver;
    }

    public function sortDriverByLocation($branch, $driverlist)
    {
        if (!isset($driverlist) || count($driverlist) == 0) {
            return $driverlist;
        }

        foreach ($driverlist as $key => $driver) {
            if (isset($driver["location"]) && $driver["location"] !== 'null') {
                $driverlist[$key]['distance'] = $this->calculateDistance($branch['latitude'], $branch['longitude'], $driver['location']['lat'], $driver['location']['lng']);
            } else {
                $driverlist[$key]['distance'] = null;
            }
        }
        array_multisort(array_column($driverlist, 'distance'), SORT_ASC, $driverlist);


        return $driverlist;
    }
}
