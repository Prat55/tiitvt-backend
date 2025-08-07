<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'duration',
        'mrp',
        'price',
        'is_active',
    ];

    protected $casts = [
        'mrp' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    /**
     * Get the category that owns the course.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(CourseCategory::class);
    }

    /**
     * Get the students for the course.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Get the exams for the course.
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * Get the certificates for the course.
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Scope to get only active courses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
