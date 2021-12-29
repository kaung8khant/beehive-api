<?php

namespace App\Repositories\Driver;

use App\Models\RestaurantBranch;

interface DriverRealtimeDataRepositoryInterface
{
    public function updateDriverStatus($driverSlug, $status);

    public function getAvailableDrivers($exclude = []);

    public function sortDriverByLocation(RestaurantBranch $branch, $driverList);
}
