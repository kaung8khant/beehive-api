<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopBranch extends Model
{
    use HasFactory;

    protected $fillable = [ 'slug', 'name', 'name_mm', 'enable', 'address', 'contact_number', 'opening_time', 'closing_time', 'latitude', 'longitude'] ;
}
