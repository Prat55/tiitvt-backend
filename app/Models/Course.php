<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'image',
        'description',
        'meta_description',
        'duration',
        'mrp',
        'price',
        'rating',
        'lectures',
        'is_active',
        'auto_certificate',
        'passing_percentage',
    ];

    protected $casts = [
        'mrp' => 'decimal:2',
        'price' => 'decimal:2',
        'lectures' => 'array',
        'is_active' => 'boolean',
        'auto_certificate' => 'boolean',
        'passing_percentage' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($course) {
            if (empty($course->slug)) {
                $course->slug = Str::slug($course->name);
            }
        });

        static::updating(function ($course) {
            if ($course->isDirty('name') && empty($course->slug)) {
                $course->slug = Str::slug($course->name);
            }
        });
    }

    /**
     * Get the categories for the course.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'course_categories');
    }

    /**
     * Get the course categories pivot table records.
     */
    public function courseCategories(): HasMany
    {
        return $this->hasMany(CourseCategory::class);
    }

    /**
     * Get the students enrolled in this course.
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_courses')
            ->withPivot([
                'enrollment_date',
                'course_taken',
                'batch_time',
                'scheme_given',
                'incharge_name'
            ])
            ->withTimestamps();
    }

    /**
     * Get the student course enrollments for this course.
     */
    public function studentCourses(): HasMany
    {
        return $this->hasMany(StudentCourse::class);
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
     * Get the course inquiries.
     */
    public function inquiries(): HasMany
    {
        return $this->hasMany(\App\Models\CourseInquiry::class);
    }

    /**
     * Scope to get only active courses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the formatted price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return '₹' . number_format($this->price, 2);
    }

    /**
     * Get the formatted MRP.
     */
    public function getFormattedMrpAttribute(): string
    {
        return '₹' . number_format($this->mrp, 2);
    }

    /**
     * Get the discount percentage.
     */
    public function getDiscountPercentageAttribute(): ?float
    {
        if ($this->mrp && $this->price && $this->mrp > $this->price) {
            return round((($this->mrp - $this->price) / $this->mrp) * 100);
        }
        return null;
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%");
    }
}
