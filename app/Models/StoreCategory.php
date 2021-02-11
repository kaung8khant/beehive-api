<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'name_mm',
        'slug',
    ];

    /**
     * Get the sub categories for the store category.
     */
    public function sub_categories()
    {
        return $this->hasMany(SubCategory::class);
    }
}
