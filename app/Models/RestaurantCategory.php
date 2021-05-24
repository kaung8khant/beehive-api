<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *      @OA\Xml(name="RestaurantCategory"),
 *      @OA\Property(property="name", type="string", example="Name"),
 *      @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */
class RestaurantCategory extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $appends = ['images'];

    public function getImagesAttribute()
    {
        $menuId = Menu::where('restaurant_category_id', $this->id)->inRandomOrder()->first();

        return File::where('source', 'restaurant_categories')
            ->where('source_id', $menuId)
            ->where('type', 'image')
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
    }

    public function restaurants()
    {
        return $this->belongsToMany(Restaurant::class, 'restaurant_restaurant_category_map');
    }

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }
}
