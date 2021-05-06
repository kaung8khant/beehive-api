<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'file_name',
        'extension',
        'source',
        'source_id',
        'type',
    ];

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
            $url = url("/api/v2/images/{$this->attributes['slug']}");
        } else {
            $url = url("/api/v2/files/{$this->attributes['slug']}");
        }

        return $url;
    }
}
