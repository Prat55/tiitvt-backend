<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    protected $fillable = ['title', 'slug', 'content', 'meta_description', 'image', 'is_active'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'blog_tags');
    }
}
