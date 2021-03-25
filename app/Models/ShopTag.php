<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *      @OA\Xml(name="ShopTag"),
 *      @OA\Property(property="name", type="string", example="name"),
 *      @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */
class ShopTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'pivot',
    ];

    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'shop_shop_tag_map');
    }
}
