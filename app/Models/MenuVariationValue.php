<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuVariationValue extends Model
{
    use HasFactory;

    protected $fillable = ['slug',"name","value","price"];

    public function menu_variations()
    {
        return $this->belongsTo(MennVariation::class);
    }
}
