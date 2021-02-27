<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'username',
        'name',
        'phone_number',
        'vehicle_number'
    ];

    public function user()
    {
        return $this->hasOne(User::class);
    }
}
