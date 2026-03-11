<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLectureProgress extends Model
{
    use HasFactory;

    protected $table = 'student_lecture_progress';

    protected $fillable = [
        'student_id',
        'course_id',
        'category_id',
        'lecture_key',
        'lecture_title',
        'duration_seconds',
        'position_seconds',
        'watched_seconds',
        'is_completed',
        'completed_at',
        'last_watched_at',
    ];

    protected $casts = [
        'student_id' => 'integer',
        'course_id' => 'integer',
        'category_id' => 'integer',
        'duration_seconds' => 'decimal:3',
        'position_seconds' => 'decimal:3',
        'watched_seconds' => 'decimal:3',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'last_watched_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
