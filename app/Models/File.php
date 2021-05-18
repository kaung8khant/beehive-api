<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'source',
        'source_id',
        'created_at',
        'updated_at',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        if ($this->attributes['extension'] === 'png' || $this->attributes['extension'] === 'jpg') {
            $url = "/api/v2/images/{$this->attributes['slug']}";
        } else {
            $url = "/api/v2/files/{$this->attributes['slug']}";
        }

        return config('app.url') . $url;
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
