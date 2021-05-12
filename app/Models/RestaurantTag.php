<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *      @OA\Xml(name="RestaurantTag"),
 *      @OA\Property(property="name", type="string", example="Product Name"),
 *      @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */
class RestaurantTag extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    public function restaurants()
    {
        return $this->belongsToMany(Restaurant::class, 'restaurant_restaurant_tag_map');
    }
}
