<?php

namespace Tests\Unit;

use App\Exceptions\BadRequestException;
use App\Models\RestaurantOrderDriverStatus;
use App\Repositories\RestaurantOrderDriverStatusRepository;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use PHPUnit\Framework\TestCase;

class RestaurantOrderDriverStatusRepositoryTest extends TestCase
{
    private $repository;
    private RestaurantOrderDriverStatus $model;

    public function setUp(): void
    {
        $this->model = Mockery::mock(RestaurantOrderDriverStatus::class);
        $this->repository = new RestaurantOrderDriverStatusRepository($this->model);
        parent::setUp();
    }

    public function tearDown(): void
    {
        $this->repository = null;
        parent::tearDown();
    }

    public function provideCorrectStatuses()
    {
        return [
            ["pending", "accepted", "pending"],
            ["pending", "rejected", "pending"],
            ["accepted", "pickUp", "pickUp"],
            ["pickUp", "delivered", "pickUp"]
        ];
    }

    public function provideWrongStatuses()
    {
        return [
            ["pending", "pending", "pending"],
            ["pending", "pickUp", "pending"],
            ["pending", "delivered", "pending"],
            ["pending", "cancelled", "pending"],
            ["accepted", "pending", "pending"],
            ["accepted", "accepted", "pending"],
            ["accepted", "pickUp", "pending"],
            ["accepted", "pickUp", "preparing"],
            ["accepted", "delivered", "pending"],
            ["accepted", "rejected", "pending"],
            ["accepted", "cancelled", "pending"],
            ["pickUp", "pending", "pickUp"],
            ["pickUp", "accepted", "pickUp"],
            ["pickUp", "pickUp", "pickUp"],
            ["pickUp", "rejected", "pickUp"],
            ["pickUp", "cancelled", "pickUp"],
            ["delivered", "pending", "pending"],
            ["delivered", "accepted", "pending"],
            ["delivered", "pickUp", "pending"],
            ["delivered", "delivered", "pending"],
            ["delivered", "rejected", "pending"],
            ["delivered", "cancelled", "pending"],
            ["rejected", "pending", "pending"],
            ["rejected", "accepted", "pending"],
            ["rejected", "pickUp", "pending"],
            ["rejected", "delivered", "pending"],
            ["rejected", "rejected", "pending"],
            ["rejected", "cancelled", "pending"],
            ["cancelled", "pending", "pending"],
            ["cancelled", "accepted", "pending"],
            ["cancelled", "pickUp", "pending"],
            ["cancelled", "delivered", "pending"],
            ["cancelled", "rejected", "pending"],
            ["cancelled", "cancelled", "pending"],
        ];
    }

    /**
     * @dataProvider provideCorrectStatuses
     */
    public function test_validate_status_with_correct_values($currentDriverStatus, $newDriverStatus, $currentOrderStatus)
    {
        // order status: 'pending','preparing','pickUp','onRoute','delivered','cancelled'
        // driver status: 'pending', 'accepted', 'pickUp', 'delivered', 'rejected', 'cancelled'
        $this->assertTrue($this->repository->validateStatus($currentDriverStatus, $newDriverStatus, $currentOrderStatus));
    }

    /**
     * @dataProvider provideWrongStatuses
     */
    public function test_validate_status_with_wrong_values($currentDriverStatus, $newDriverStatus, $currentOrderStatus)
    {
        $this->expectException(BadRequestException::class);
        $this->repository->validateStatus($currentDriverStatus, $newDriverStatus, $currentOrderStatus);
    }
}
