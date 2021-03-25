<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *      @OA\Xml(name="RestaurantTag"),
 *      @OA\Property(property="name", type="string", example="Product Name"),
 *      @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */
class RestaurantTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

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
