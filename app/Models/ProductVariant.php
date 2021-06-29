<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'product_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_enable' => 'boolean',
        'variant' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}