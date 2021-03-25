<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *      @OA\Xml(name="Promocode"),
 *      @OA\Property(property="slug", type="string", readOnly=true),
 *      @OA\Property(property="code", type="string", example="00012"),
 *      @OA\Property(property="type", type="string", example="fix"),
 *      @OA\Property(property="usage", type="string", example="shop"),
 *      @OA\Property(property="amount", type="number", example=1000),
 *      @OA\Property(property="description", type="string", example="description"),
 *      @OA\Property(property="rules", type="array", @OA\Items(oneOf={
 *       @OA\Schema(
 *         @OA\Property(property="value", type="string", example="value"),
 *         @OA\Property(property="data_type", type="string", example="before date"),
 *        ),
 *      })),
 * )
 */
class Promocode extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'code',
        'type',
        'usage',
        'amount',
        'description',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function rules()
    {
        return $this->hasMany(PromocodeRule::class);
    }
}
