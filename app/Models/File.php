<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'slug',
        'file_name',
        'extension',
        'source',
        'source_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function getUrlAttribute()
    {
        if ($this->attributes['extension'] === 'png' || $this->attributes['extension'] === 'jpg') {
            $url = url("/api/v2/images/{$this->attributes['slug']}");
        } else {
            $url = url("/api/v2/files/{$this->attributes['slug']}");
        }

        return $url;
    }

    protected $appends = ['url'];
}
