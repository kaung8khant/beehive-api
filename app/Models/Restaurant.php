<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *      @OA\Xml(name="Restaurant"),
 *      @OA\Property(property="name", type="string", example="Restaurant Name"),
 *      @OA\Property(property="name_mm", type="string", example="ဆိုင်အမည်"),
 *      @OA\Property(property="is_enable", type="boolean", example="true"),
 *      @OA\Property(property="availableTags", type="array", @OA\Items(oneOf={
 *        @OA\Schema(
 *           type="string",example="CB965585"
 *           ),
 *     })),
 *     @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'name_mm',
        'is_enable',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $casts = [
        'is_enable' => 'boolean',
    ];

    public function availableTags()
    {
        return $this->belongsToMany(RestaurantTag::class, 'restaurant_restaurant_tag_map');
    }

    public function availableCategories()
    {
        return $this->belongsToMany(RestaurantCategory::class, 'restaurant_restaurant_category_map');
    }

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }

    public function restaurantBranches()
    {
        return $this->hasMany(RestaurantBranch::class);
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'favorite_restaurant');
    }
}
