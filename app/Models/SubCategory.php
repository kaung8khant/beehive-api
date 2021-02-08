<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name','name_mm', 'slug', 'store_category_id'];

    public function store_category()
    {
        return $this->belongsTo(StoreCategory::class);
    }
}
