<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuVariation extends Model
{
    use HasFactory;

    protected $fillable = ['slug',"name","description"];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
    public function menu_variation_values()
    {
        return $this->hasMany(MenuVariationValue::class);
    }
}
