<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *      @OA\Xml(name="MenuVariationValue"),
 *      @OA\Property(property="value", type="string", example="Name"),
 *      @OA\Property(property="price", type="number", example=1000),
 *      @OA\Property(property="menu_variation_slug", type="string", example="D16AAF"),
 *      @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */
class MenuVariationValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'value',
        'price',
        'menu_variation_id',
    ];

    protected $hidden = [
        'id',
        'menu_variation_id',
        'created_at',
        'updated_at',
    ];

    protected $appends = ['images'];

    public function getImagesAttribute()
    {
        return File::where('source', 'menu_variation_values')
            ->where('source_id', $this->id)
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
    }

    public function menuVariation()
    {
        return $this->belongsTo(MenuVariation::class);
    }
}
