<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
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
     * Get the townships for the city.
     */
    public function townships()
    {
        return $this->hasMany(Township::class);
    }
}
