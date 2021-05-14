<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *      @OA\Xml(name="Menu"),
 *      @OA\Property(property="name", type="string", example="Name"),
 *      @OA\Property(property="description", type="string", example="description"),
 *      @OA\Property(property="restaurant_slug", type="string", example="D16AAF"),
 *      @OA\Property(property="restaurant_category_slug", type="string", example="E16AAF"),
 *      @OA\Property(property="is_enable", type="boolean", example=true),
 *      @OA\Property(property="price", type="decimal", example=100.00),
 *      @OA\Property(property="tax", type="number", example=20),
 *      @OA\Property(property="slug", type="string", readOnly=true),
 *      @OA\Property(property="menu_variations", type="array", @OA\Items(oneOf={
 *       @OA\Schema(
 *          @OA\Property(property="name", type="string", example="Name"),
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
 *         @OA\Property(property="price", type="number", example=1000),
 *        ),
 *      })),
 * )
 */
class Menu extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

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

    protected $appends = ['images'];

    public function getImagesAttribute()
    {
        return File::where('source', 'menus')
            ->where('source_id', $this->id)
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
    }

    public function getIsAvailableAttribute()
    {
        return boolval($this->pivot->is_available);
    }

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
        return $this->belongsToMany(RestaurantBranch::class, 'restaurant_branch_menu_map')->withPivot('is_available');
    }
}
