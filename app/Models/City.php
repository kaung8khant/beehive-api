<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *      @OA\Xml(name="City"),
 *      @OA\Property(property="name", type="string", example="City Name"),
 *      @OA\Property(property="name_mm", type="string", example="မြို့အမည်"),
 *      @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */
class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_mm',
        'slug',
    ];

    public function townships()
    {
        return $this->hasMany(Township::class);
    }
}
