<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuTopping extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'name_mm',
        'description',
        'description_mm',
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

    public function menu_topping_values()
    {
        return $this->hasMany(MenuToppingValue::class);
    }
}
