<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [ 'slug' , 'name' , 'name_mm' , 'official' , 'enable'] ;

    /**
    * The attributes that should be hidden for arrays.
    *
    * @var array
    */
    protected $hidden = [
        'id',
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'pivot',
    ];

    public function restaurant_tags()
    {
        return $this->belongsToMany(RestaurantTag::class, 'tag_restaurant');
    }

    public function restaurant_categories()
    {
        return $this->belongsToMany(RestaurantCategory::class, 'category_restaurant');
    }
}
