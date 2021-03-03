<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promocode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'usage',
        'amount',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
    ];
}
