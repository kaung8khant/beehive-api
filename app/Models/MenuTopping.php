<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *      @OA\Xml(name="MenuTopping"),
 *      @OA\Property(property="name", type="string", example="Name"),
 *      @OA\Property(property="menu_slug", type="string", example="D16AAF"),
 *      @OA\Property(property="price", type="number", example=1000),
 *      @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */
class MenuTopping extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'menu_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_incremental' => 'boolean',
    ];

    protected $appends = ['images'];

    public function getImagesAttribute()
    {
        return File::where('source', 'menu_toppings')
            ->where('source_id', $this->id)
            ->where('type', 'image')
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
