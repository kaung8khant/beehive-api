<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name','name_mm', 'slug'];

    public function sub_categories()
    {
        return $this->hasMany(SubCategory::class);
    }
}
