<?php

namespace App\Repositories\Driver;

use App\Repositories\BaseRepositoryInterface;

interface DriverRepositoryInterface extends BaseRepositoryInterface
{
    public function getAllDriver($sorting = array('orderBy' => "", sortBy => ""));

    public function getDriverWithPhone($phone);

    public function update($id, $data);

    public function getOtherUserWithPhone($phone, $driverID);
}
