<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuCartItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'menu_cart_id',
        'menu_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'menu' => 'array',
    ];
}
