<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuTopping extends Model
{
    use HasFactory;
    /**
    * The attributes that are mass assignable.
    *
    * @var array
    */
    protected $fillable = ['slug',"name","description","menu_id"];

    public function menus()
    {
        return $this->belongsTo(Menu::class);
    }

    public function menu_topping_values()
    {
        return $this->hasMany(MenuToppingValue::class);
    }
}
