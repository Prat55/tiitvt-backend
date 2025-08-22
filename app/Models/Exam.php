<?php

namespace App\Models;

use App\Enums\ExamStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'password',
        'course_id',
        'student_id',
        'duration',
        'date',
        'start_time',
        'end_time',
        'status',
    ];

    protected $casts = [
        'duration' => 'integer',
        'status' => ExamStatusEnum::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($exam) {
            if (empty($exam->exam_id)) {
                $exam->exam_id = self::generateUniqueExamId();
            }
            if (empty($exam->password)) {
                $exam->password = self::generatePassword();
            }
        });
    }

    /**
     * Generate a unique exam ID
     */
    public static function generateUniqueExamId(): string
    {
        do {
            $examId = 'EXAM' . strtoupper(Str::random(8));
        } while (self::where('exam_id', $examId)->exists());

        return $examId;
    }

    /**
     * Generate a random password for exam access
     */
    public static function generatePassword(): string
    {
        return Str::random(6);
    }

    /**
     * Get the course that owns the exam.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(ExamCategory::class);
    }

    /**
     * Get the actual category models for the exam
     */
    public function examCategories(): HasMany
    {
        return $this->hasMany(ExamCategory::class);
    }

    /**
     * Get the category names for the exam
     */
    public function getCategoryNamesAttribute(): array
    {
        return $this->examCategories()->with('category')->get()
            ->pluck('category.name')
            ->filter()
            ->toArray();
    }

    /**
     * Get the questions for the exam.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Get the exam results for the exam.
     */
    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    /**
     * Scope to get only active exams.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get scheduled exams that are past their end time
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', ExamStatusEnum::SCHEDULED)
            ->where('date', '<=', now()->toDateString())
            ->where('end_time', '<', now()->format('H:i:s'));
    }

    public function scopeSearch($query, $search)
    {
        return $query->whereHas('course', function ($q) use ($search) {
            $q->search($search);
        })->orWhereHas('student', function ($q) use ($search) {
            $q->search($search);
        });
    }
}
