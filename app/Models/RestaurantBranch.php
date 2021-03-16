<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *      @OA\Xml(name="RestaurantBranch"),
 *      @OA\Property(property="name", type="string", example="name"),
 *      @OA\Property(property="name_mm", type="string", example="name_mm"),
 *      @OA\Property(property="address", type="string", example="address"),
 *      @OA\Property(property="contact_number", type="string", example="string"),
 *      @OA\Property(property="opening_time", type="time", example="00:00"),
 *      @OA\Property(property="closing_time", type="time", example="00:00"),
 *      @OA\Property(property="latitude", type="double", example="0.00"),
 *      @OA\Property(property="longitude", type="double", example="0.00"),
 *      @OA\Property(property="restaurant_slug", type="string", example="restaurant_slug"),
 *      @OA\Property(property="township_slug", type="string", example="township_slug"),
 *      @OA\Property(property="is_enable", type="boolean", example="true"),
 *      @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */

class RestaurantBranch extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'name_mm',
        'is_enable',
        'address',
        'contact_number',
        'opening_time',
        'closing_time',
        'latitude',
        'longitude',
        'restaurant_id',
        'township_id',
    ];

    protected $hidden = [
        'id',
        'restaurant_id',
        'township_id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $casts = [
        'is_enable' => 'boolean',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        return '';
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function township()
    {
        return $this->belongsTo(Township::class);
    }

    public function availableMenus()
    {
        return $this->belongsToMany(Menu::class, 'restaurant_branch_menu_map')->withPivot('is_available');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'users', 'id');
    }
}
