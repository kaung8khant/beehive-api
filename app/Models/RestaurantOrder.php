<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *      @OA\Xml(name="RestaurantOrder"),
 *      @OA\Property(property="order_date", type="string", example="2021-02-19"),
 *      @OA\Property(property="restaurant_branch_slug", type="string", example=""),
 *      @OA\Property(property="customer_slug", type="string", example="D16AAF"),
 *      @OA\Property(property="special_instruction", type="string", example="special_instruction"),
 *      @OA\Property(property="payment_mode", type="string", example="COD"),
 *      @OA\Property(property="delivery_mode", type="string", example="delivery"),
 *      @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */
class RestaurantOrder extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'customer_id',
        'restaurant_id',
        'restaurant_branch_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'restaurant_branch_info' => AsArrayObject::class,
    ];

    protected $appends = ['invoice_id', 'total_amount'];

    public function getInvoiceIdAttribute()
    {
        return sprintf('%08d', $this->id);
    }

    public function getTotalAmountAttribute()
    {
        $orderItems = $this->restaurantOrderItems;
        $totalAmount = 0;

        foreach ($orderItems as $item) {
            $amount = $item->amount + $item->tax - $item->discount;
            $totalAmount += $amount;
        }

        return $totalAmount - $this->promocode_amount;
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function restaurantBranch()
    {
        return $this->belongsTo(RestaurantBranch::class);
    }

    public function restaurantOrderStatuses()
    {
        return $this->hasMany(RestaurantOrderStatus::class);
    }

    public function restaurantOrderContact()
    {
        return $this->hasOne(RestaurantOrderContact::class);
    }

    public function restaurantOrderItems()
    {
        return $this->hasMany(RestaurantOrderItem::class);
    }

    public function restaurantRatings()
    {
        return $this->hasMany(RestaurantRating::class);
    }
}
