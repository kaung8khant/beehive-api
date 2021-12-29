<?php

namespace App\Repositories\Driver;

use App\Models\User;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Log;

class DriverRepository extends BaseRepository implements DriverRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function getAllDriver($sorting = array('orderBy' => "", sortBy => ""))
    {
        $filter = request('filter') ? request('filter') : '';

        return User::with('roles')
            ->whereHas('roles', function ($q) {
                $q->where('name', 'Driver');
            })
            ->where(function ($q) use ($filter) {
                $q->where('username', 'LIKE', '%' . $filter . '%')
                    ->orWhere('name', 'LIKE', '%' . $filter . '%')
                    ->orWhere('phone_number', 'LIKE', '%' . $filter . '%')
                    ->orWhere('slug', $filter);
            })
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function getDriverWithPhone($phone)
    {
        return User::where('phone_number', $phone)->first();
    }

    public function getOtherUserWithPhone($phone, $driverID)
    {
        return User::where('phone_number', $phone)->where('id', '<>', $driverID)->first();
    }

    public function update($id, $data)
    {
        $driver = User::find($id);

        $driver->update($data);

        if (!$driver->roles->contains('name', 'Driver')) {
            $driverRoleId = Role::where('name', 'Driver')->value('id');
            $driver->roles()->attach($driverRoleId);
        }

        if (array_key_exists('image_slug', $data)) {
            $this->updateFile($data['image_slug'], 'users', $driver->slug);
        }
        return $driver;
    }
}
