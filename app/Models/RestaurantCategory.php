<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name','name_mm', 'slug'];

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }
}
