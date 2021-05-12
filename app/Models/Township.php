<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *      @OA\Xml(name="Township"),
 *      @OA\Property(property="name", type="string", example="Tamwe"),
 *      @OA\Property(property="city_slug", type="string", example="8998B3"),
 *      @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */
class Township extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'city_id',
        'created_at',
        'updated_at',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function shops()
    {
        return $this->hasMany(Shop::class);
    }

    public function restaurantBranches()
    {
        return $this->hasMany(RestaurantBranch::class);
    }
}
