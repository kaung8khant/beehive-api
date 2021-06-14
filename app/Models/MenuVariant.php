<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuVariant extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'menu_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_enable' => 'boolean',
        'variant' => 'array',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
