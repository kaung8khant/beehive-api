<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuOption extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'menu_id',
        'created_at',
        'updated_at',
    ];

    public function options()
    {
        return $this->hasMany(MenuOptionItem::class, 'menu_option_id');
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
