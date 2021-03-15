<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Type\Decimal;

/**
 * @OA\Schema(
 *      @OA\Xml(name="Menu"),
 *      @OA\Property(property="name", type="string", example="Name"),
 *      @OA\Property(property="name_mm", type="string", example="အမည်"),
 *      @OA\Property(property="description", type="string", example="description"),
 *      @OA\Property(property="description_mm", type="string", example="description mm"),
 *      @OA\Property(property="restaurant_slug", type="string", example="D16AAF"),
 *      @OA\Property(property="restaurant_category_slug", type="string", example="E16AAF"),
 *      @OA\Property(property="is_enable", type="boolean", example=true),
 *      @OA\Property(property="price", type="number", example=1000),
 *      @OA\Property(property="slug", type="string", readOnly=true),
 *      @OA\Property(property="menu_variations", type="array", @OA\Items(oneOf={
 *       @OA\Schema(
 *          @OA\Property(property="name", type="string", example="Name"),
 *          @OA\Property(property="name_mm", type="string", example="အမည်"),
 *          @OA\Property(property="menu_variation_values", type="array", @OA\Items(oneOf={
 *                @OA\Schema(
 *                   @OA\Property(property="value", type="string", example="Name"),
 *                   @OA\Property(property="price", type="number", example=1000),
 *                  ),
 *                })),
 *         ),
 *       })),
 *    @OA\Property(property="menu_toppings", type="array", @OA\Items(oneOf={
 *     @OA\Schema(
 *         @OA\Property(property="name", type="string", example="Name"),
 *         @OA\Property(property="name_mm", type="string", example="NameMM"),
 *         @OA\Property(property="price", type="number", example=1000),
 *        ),
 *      })),
 * )
 */
class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'name_mm',
        'description',
        'description_mm',
        'price',
        'restaurant_id',
        'restaurant_category_id',
        'is_enable',
    ];

    protected $hidden = [
        'id',
        'restaurant_id',
        'restaurant_category_id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $casts = [
        'is_enable' => 'boolean',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function restaurantCategory()
    {
        return $this->belongsTo(RestaurantCategory::class);
    }

    public function menuVariations()
    {
        return $this->hasMany(MenuVariation::class);
    }

    public function menuToppings()
    {
        return $this->hasMany(MenuTopping::class);
    }

    public function restaurantBranches()
    {
        return $this->belongsToMany(RestaurantBranch::class, 'restaurant_branch_menu_map');
    }
}
