<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *      @OA\Xml(name="Setting"),
 *       @OA\Items(type="object",
 *       @OA\Property(property="key", type="string", example="33"),
 *       @OA\Property(property="value", type="string", example="Name"),
 *       @OA\Property(property="data_type", type="string", example="string")
 *   ),
 * )
 */

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'data_type'
    ];
}
