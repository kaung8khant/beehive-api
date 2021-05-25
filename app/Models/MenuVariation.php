<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuVariation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'menu_id',
        'created_at',
        'updated_at',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function menuVariationValues()
    {
        return $this->hasMany(MenuVariationValue::class);
    }
}
