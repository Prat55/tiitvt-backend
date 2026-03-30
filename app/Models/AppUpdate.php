<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppUpdate extends Model
{
    protected $fillable = [
        'type',
        'version',
        'version_code',
        'apk_path',
        'changelog',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'version_code' => 'integer',
    ];

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }
}
