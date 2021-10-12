<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class Brand extends BaseModel
{
    use HasFactory, Searchable;

    protected $guarded = ['id'];

    protected $hidden = [
        // 'id',
        'created_at',
        'updatd_at',
    ];

    protected $appends = ['images'];

    public function toSearchableArray(): array
    {
        return $this->toArray();
    }

    public function getImagesAttribute()
    {
        return File::where('source', 'brands')
            ->where('source_id', $this->id)
            ->where('type', 'image')
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
