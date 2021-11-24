<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'price' => 'float',
        'is_incremental' => 'boolean',
        'max_quantity' => 'integer',
    ];

    protected $appends = ['images'];

    public function getImagesAttribute()
    {
        return File::where('source', 'menu_toppings')
            ->where('source_id', $this->id)
            ->where('type', 'image')
            ->whereIn('extension', ['png', 'jpg', 'jpeg'])
            ->get();
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
