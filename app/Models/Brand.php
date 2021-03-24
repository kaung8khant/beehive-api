<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *      @OA\Xml(name="Brand"),
 *      @OA\Property(property="name", type="string", example="Brand Name"),
 *      @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */
class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name'
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updatd_at'
    ];


    protected $appends = ['images'];

    public function getImagesAttribute()
    {
        return File::where('source', 'brands')
            ->where('source_id', $this->id)
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
