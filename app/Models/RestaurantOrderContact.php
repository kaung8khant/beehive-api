<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *      @OA\Xml(name="RestaurantOrderContact"),
 *      @OA\Property(property="customer_name", type="string", example="Mg Mg"),
 *      @OA\Property(property="phone_number", type="string", example="0996778856"),
 *      @OA\Property(property="house_number", type="string", example="No. 31"),
 *      @OA\Property(property="floor", type="string", example="3rd Floor"),
 *      @OA\Property(property="street_name", type="string", example="Alone Street"),
 *      @OA\Property(property="latitude", type="number", example="21.962705"),
 *      @OA\Property(property="longitude", type="number", example="96.106227"),
 *      @OA\Property(property="restaurant_order_id", type="number"),
 *      @OA\Property(property="township_id", type="number"),
 * )
 */
class RestaurantOrderContact extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'restaurant_order_id',
        'township_id',
        'created_at',
        'updated_at',
    ];

    public function restaurantOrder()
    {
        return $this->belongsTo(RestaurantOrder::class);
    }

    public function township()
    {
        return $this->belongsTo(Township::class);
    }
}
