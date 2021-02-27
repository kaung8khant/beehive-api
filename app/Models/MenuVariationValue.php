<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuVariationValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'value',
        'price',
        'menu_variation_id',
    ];

    protected $hidden = [
        'id',
        'menu_variation_id',
        'created_at',
        'updated_at',
    ];

    public function menuVariation()
    {
        return $this->belongsTo(MenuVariation::class);
    }
}
