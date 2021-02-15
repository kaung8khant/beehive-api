<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuVariationValue extends Model
{
    use HasFactory;
    /**
    * The attributes that are mass assignable.
    *
    * @var array
    */

    protected $fillable = ['slug',"name","value","price","menu_variation_id"];

    public function menu_variations()
    {
        return $this->belongsTo(MenuVariation::class);
    }
}
