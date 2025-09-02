<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['name', 'slug', 'is_active'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function blogs()
    {
        return $this->belongsToMany(Blog::class, 'blog_tags');
    }
}
