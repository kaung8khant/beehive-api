<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuTopping extends Model
{
    use HasFactory;
    protected $fillable = ['slug',"name","description"];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
