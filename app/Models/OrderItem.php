<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *      @OA\Xml(name="OrderItem"),
 *       @OA\Property(property="item_slug", type="string", example="D16AAF"),
 *       @OA\Property(property="item_name", type="string",example="name"),
 *       @OA\Property(property="item_type", type="string",example="product"),
 *       @OA\Property(property="amount", type="number",example=0.00),
 *       @OA\Property(property="quantity", type="number",example=0.00),
 *       @OA\Property(property="tax", type="number",example=0.00),
 *       @OA\Property(property="discount", type="number",example=0.00),
 *       @OA\Property(property="is_deleted", type="boolean",example=false),
 * )
 */

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'item_id',
        'item_name',
        'item_type',
        'amount',
        'quantity',
        'tax',
        'discount',
        'is_deleted'
    ];

    protected $hidden = [
        'id',
        'order_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}