<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *      @OA\Xml(name="Shop"),
 *      @OA\Property(property="name", type="string", example="name"),
 *      @OA\Property(property="slug", type="string", readOnly=true),
 *      @OA\Property(property="is_official", type="boolean", example=true),
 *      @OA\Property(property="is_enable", type="boolean", example=true),
 *      @OA\Property(property="address", type="string", example="somewhere"),
 *      @OA\Property(property="contact_number", type="string", example="09444456321"),
 *      @OA\Property(property="opening_time", type="string", example="06:09:00"),
 *      @OA\Property(property="closing_time", type="string", example="21:51:00"),
 *      @OA\Property(property="latitude", type="number", example=16.888501),
 *      @OA\Property(property="longitude", type="number", example=96.199375),
 *      @OA\Property(property="township_slug", type="string", example="CB91EE"),
 *      @OA\Property(property="shop_tags", type="array", @OA\Items(oneOf={
 *        @OA\Schema(
 *           type="string",example="CB965585"
 *           ),
 *     })),
 * )
 */

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'is_official',
        'is_enable',
        'address',
        'contact_number',
        'opening_time',
        'closing_time',
        'latitude',
        'longitude',
        'township_id',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
        'shop_id',
        'township_id',
    ];

    protected $casts = [
        'is_official' => 'boolean',
        'is_enable' => 'boolean',
    ];

    protected $appends = ['images'];

    public function getImagesAttribute()
    {
        return File::where('source', 'shops')
            ->where('source_id', $this->id)
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
    }

    public function availableTags()
    {
        return $this->belongsToMany(ShopTag::class, 'shop_shop_tag_map');
    }

    public function availableCategories()
    {
        return $this->belongsToMany(ShopCategory::class, 'shop_shop_category_map');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function township()
    {
        return $this->belongsTo(Township::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'users', 'id');
    }
}
