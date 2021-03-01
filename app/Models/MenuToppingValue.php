<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuToppingValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'value',
        'price',
        'menu_topping_id',
    ];

    protected $hidden = [
        'id',
        'menu_topping_id',
        'created_at',
        'updated_at',
    ];

    public function menuTopping()
    {
        return $this->belongsTo(MenuTopping::class);
    }
}
