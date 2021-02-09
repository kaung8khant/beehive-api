<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuToppingValue extends Model
{
    use HasFactory;

    protected $fillable = ['slug',"name","value","price"];

    public function menu_toppings()
    {
        return $this->belongsTo(MenuTopping::class);
    }
}
