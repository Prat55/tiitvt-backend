<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogTag extends Model
{
    protected $fillable = ['blog_id', 'tag_id'];

    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }
}
