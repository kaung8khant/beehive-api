<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'content',
        'created_by',
        'modified_by'
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
    ];
}
