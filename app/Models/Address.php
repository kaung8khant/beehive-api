<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'house_number', 'floor', 'street_name','latitude', 'longitude','is_primary','township_id'];

}