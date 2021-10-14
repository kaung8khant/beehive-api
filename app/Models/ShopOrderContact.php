<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopOrderContact extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'township_id',
        'shop_order_id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    public function getFloorAttribute()
    {
        return $this->value ? $this->value : '0';
    }

    public function shopOrder()
    {
        return $this->belongsTo(ShopOrder::class);
    }

    public function getTableColumns()
    {
        return $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
    }

    public function scopeExclude($query, $columns = [])
    {
        return $query->select(array_diff($this->getTableColumns(), (array) $columns));
    }
}
