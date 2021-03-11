<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *      @OA\Xml(name="MenuTopping"),
 *      @OA\Property(property="name", type="string", example="Name"),
 *      @OA\Property(property="name_mm", type="string", example="အမည်"),
 *      @OA\Property(property="menu_slug", type="string", example="D16AAF"),
 *      @OA\Property(property="price", type="number", example=1000),
 *      @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */

class MenuTopping extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'name_mm',
        'price',
        'menu_id',
    ];

    protected $hidden = [
        'id',
        'menu_id',
        'created_at',
        'updated_at',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
