<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *      @OA\Xml(name="OrderContact"),
 *      @OA\Property(property="order_slug", type="string", example="D16AAF"),
 *      @OA\Property(property="customer_name", type="string", example="U Ba"),
 *      @OA\Property(property="phone_number", type="string", example="09444469588"),
 *      @OA\Property(property="house_number", type="string", example="somethings"),
 *      @OA\Property(property="floor", type="string", example="first"),
 *      @OA\Property(property="street_name", type="string", example="somewhere"),
 *      @OA\Property(property="latitude", type="number", example=16.761054),
 *      @OA\Property(property="longitude", type="number", example=96.196635)
 * )
 */
class OrderContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_name',
        'phone_number',
        'house_number',
        'floor',
        'street_name',
        'latitude',
        'longitude',
    ];

    protected $hidden = [
        'id',
        'order_id',
        'created_at',
        'updated_at',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
