<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\ResponseHelper;
use App\Helpers\LocationHelper;
use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Repositories\Abstracts\DriverRealtimeDataRepositoryInterface;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    use  ResponseHelper;
    private $repository;

    public function __construct(DriverRealtimeDataRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $drivers = $this->repository->getAvailableDrivers();
        $location = array("latitude" => 21.9747952, "longitude" => 96.0814699);
        return LocationHelper::orderByNearestLocation($drivers, $location);

        $announcements = Content::where('type', 'announcement')
            ->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->orderBy('id', 'desc')
            ->paginate($request->size);

        return $this->generateProductResponse($announcements, 200, 'array', $announcements->lastPage());
    }
}
