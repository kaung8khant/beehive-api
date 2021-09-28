<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class MenuOptionItem extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'menu_option_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'price' => 'float',
    ];

    public function menuOption()
    {
        return $this->belongsTo(MenuOption::class);
    }
}
