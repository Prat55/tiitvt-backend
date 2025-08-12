<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'image',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::deleting(function ($category) {
            $category->courseCategories()->delete();
        });
    }

    public function courseCategories(): HasMany
    {
        return $this->hasMany(CourseCategory::class);
    }

    /**
     * Get the courses for the category.
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_categories');
    }

    /**
     * Scope to get only active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
